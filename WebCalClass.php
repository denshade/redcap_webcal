<?php

namespace uzgent\WebCalClass;



use mysql_xdevapi\Exception;

require_once "CreateCalFile.php";
// Declare your module class, which must extend AbstractExternalModule
class WebCalClass extends \ExternalModules\AbstractExternalModule {

    /**
     * Create all ics files in the Webcalendar directory.
     */
    public function createCalendar()
    {
        try {
            $webcaldir = $this->createWebcalDirectoryIfNeeded();
            $calFileGenerator = new \CreateCalFile();
            foreach ($this->getActiveProjects() as $activeProject)
            {
                $this->createCalendarForProject($activeProject, $calFileGenerator, $webcaldir);
            }
        } catch (\Exception $e)
        {
            error_log(var_export($e, true)); // REDCap hides exceptions... Vardumping them gives me a chance to debug them.
        }


    }

    /**
     * Gets all the project ids where the external module is enabled.
     * @return array of project ids.
     */
    private function getActiveProjects()
    {
        global $rc_connection;
        $projectIds  = [];
        $sql = "SELECT project_id FROM `redcap_external_module_settings` INNER JOIN 
 `redcap_external_modules` ON redcap_external_modules.external_module_id = redcap_external_module_settings.external_module_id
 WHERE `key` = 'enabled' and directory_prefix = 'webcal'";
        $dagQuery = mysqli_query($rc_connection, $sql);
        $dagResult = mysqli_fetch_assoc($dagQuery);
        while ($dagResult !== null) {
            $projectIds []= $dagResult["project_id"];
            $dagResult = mysqli_fetch_assoc($dagQuery);
        }
        mysqli_free_result($dagResult);
        return $projectIds;
    }

    /**
     * @return string
     */
    private function getWebcalendarDirectory()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "webcalendar";
    }

    /**
     * @param $activeProject int project id.
     * @param $salt string can potentially overwrite files on the server! Must be properly checked.
     * @param $dag int Id of the DAG. Use this for multiDAG projects.
     * @return string, never null.
     */
    public static function getFilename($activeProject, $salt, $dag = null)
    {
        if (!is_numeric($activeProject))
        {
            throw new \RuntimeException("Invalid project id:  $activeProject");
        }
        if (strpos($salt, DIRECTORY_SEPARATOR) !== false)
        {
            throw new \RuntimeException("Invalid salt, no directory traversal allowed:  $salt");
        }
        $dagPrefix = "";
        if ($dag !== null)
        {
            $dagPrefix = substr(md5($dag.$salt),0,5);
        }
        return "P" . $activeProject . "ID" . $dagPrefix . $salt . ".ics";
    }

    /**
     * @param $activeProject int project id.
     * @param \CreateCalFile $calFileGenerator Never null.
     * @param $webcaldir string directory where to generate the files
     * @throws \Exception
     */
    private function createCalendarForProject($activeProject, \CreateCalFile $calFileGenerator, $webcaldir)
    {

        $salt = $this->getProjectSetting("salt", (int)$activeProject);

        if ($salt !== null && strlen($salt) > 0) {
            if (count($this->getDagIds($activeProject))> 0){

                foreach ($this->getDagIds($activeProject) as $dagId)
                {
                    $filename = $this->getFilename($activeProject, $salt, $dagId);
                    $calFileGenerator->writeCalendar($activeProject, $webcaldir . DIRECTORY_SEPARATOR . $filename, $dagId);
                }
            } else {
                $filename = $this->getFilename($activeProject, $salt);
                $calFileGenerator->writeCalendar($activeProject, $webcaldir . DIRECTORY_SEPARATOR . $filename);
            }

        }
        //If no salt then skip.
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function createWebcalDirectoryIfNeeded()
    {
        $webcaldir = $this->getWebcalendarDirectory();
        if (!file_exists($webcaldir)) {
            $success = mkdir($webcaldir);
            if (!$success) {
                throw new \Exception("Cannot create webcalendar directory");
            }
        }
        return $webcaldir;
    }

    /**
     * @param $activeProject
     * @return array|null
     * @throws \Exception
     */
    public function getDagIds($activeProject)
    {
        if (!is_numeric($activeProject))
        {
            throw new \Exception("project id must be numeric");
        }
        $dags = $this->getDags($activeProject);
        $dagIds = [];
        foreach ($dags as $dag)
        {
            $dagIds []= $dag["group_id"];
        }
        return $dagIds;
    }

    /**
     * @param $activeProject
     * @return array|null
     * @throws \Exception
     */
    public function getDags($activeProject)
    {
        global $rc_connection;
        $dags  = [];
        $sql = "SELECT * FROM `redcap_data_access_groups` WHERE project_id = ". $activeProject;
        $dagQuery = mysqli_query($rc_connection, $sql);
        $dagResult = mysqli_fetch_assoc($dagQuery);
        while ($dagResult !== null) {
            $dags []= $dagResult;
            $dagResult = mysqli_fetch_assoc($dagQuery);
        }
        mysqli_free_result($dagResult);
        return $dags;
    }

    /**
     * @param $activeProject
     * @return array|null
     * @throws \Exception
     */
    public function getDagForUser($activeProject, $userid)
    {
        $userrights = \UserRights::getPrivileges($activeProject, $userid);
        $value = $userrights[$activeProject][$userid];
        $group_id = $value["group_id"];
        $dags = $this->getDags($activeProject);
        if ($group_id === null)
        {
            return $dags;
        } else {
            foreach($dags as $dag)
            {
                if ($dag["group_id"] == $group_id) //return the dag that matches the group.
                {
                    return [$dag];
                }
            }
        }
        return [];
    }


}
