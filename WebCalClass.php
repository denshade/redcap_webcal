<?php

namespace uzgent\WebCalClass;



require_once "CreateCalFile.php";
// Declare your module class, which must extend AbstractExternalModule
class WebCalClass extends \ExternalModules\AbstractExternalModule {

    /**
     * Create all ics files in the Webcalendar directory.
     */
    public function createCalendar()
    {
        $webcaldir = $this->getWebcalendarDirectory();
        if (!file_exists($webcaldir))
        {
            $success = mkdir($webcaldir);
            if (!$success){
                throw new \Exception("Cannot create webcalendar directory");
            }
        }
        try {
            $calFileGenerator = new \CreateCalFile();
            foreach ($this->getActiveProjects() as $activeProject)
            {
                $salt = $this->getProjectSetting("salt", $activeProject);
                if ($salt === null || strlen($salt) === 0)
                {
                    continue; //Skip projects without a salt.
                }
                $filename = $this->getFilename($activeProject, $salt);
                $calFileGenerator->writeCalendar($activeProject, $webcaldir . DIRECTORY_SEPARATOR . $filename);
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
        $result = db_query("SELECT project_id FROM `redcap_external_module_settings` INNER JOIN 
 `redcap_external_modules` ON redcap_external_modules.external_module_id = redcap_external_module_settings.external_module_id
 WHERE `key` = 'enabled' and directory_prefix = 'webcal'");
        return db_fetch_array($result,MYSQLI_NUM);
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
     * @return string
     */
    public function getFilename($activeProject, $salt)
    {
        if (!is_numeric($activeProject))
        {
            throw new \RuntimeException("Invalid project id:  $activeProject");
        }
        if (strpos($salt, DIRECTORY_SEPARATOR) !== false)
        {
            throw new \RuntimeException("Invalid salt, no directory traversal allowed:  $salt");
        }
        return "P" . $activeProject . "ID" . $salt . ".ics";
    }


}
