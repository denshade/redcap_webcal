<?php

require_once "vendor/autoload.php";

class CreateCalFile
{
    /**
     * @param $projectId
     * @return string
     * @throws Exception
     */
    public function generate($projectId, $dagId = null)
    {
        if ($projectId === null || !is_numeric($projectId)) {
            throw new Exception("Invalid project id " . $projectId);
        }
        //Select the data from the database.
        $events = $this->getCalEvents($projectId, $dagId);
        $vCalendar = new \Eluceo\iCal\Component\Calendar($projectId);
        foreach($events as $event)
        {
            $vEvent = $this->createEventFromRecord($event);
            $vCalendar->addComponent($vEvent);
        }
        return $vCalendar->render();
    }

    /**
     * @param $projectId
     * @param $filename
     * @throws Exception
     */
    public function writeCalendar($projectId, $filename, $dagId = null)
    {
        $success = file_put_contents($filename, $this->generate($projectId, $dagId));
        if (!$success) {
            throw new Exception("Unable to write file to " . $filename);
        }
    }

    /**
     * @param $projectId
     * @return array
     * @throws Exception
     */
    public function getCalEvents($projectId, $dagId = null)
    {
        if ($projectId === null || !is_numeric($projectId)) {
            throw new Exception("Invalid project id " . $projectId);
        }
        if ($dagId == null){
            $sql = "select * from redcap_events_metadata m right outer join redcap_events_calendar c on c.event_id = m.event_id
			where c.project_id = " . $projectId ." order by c.event_date, c.event_time";
        } else {
            $sql = "select * from redcap_events_metadata m right outer join redcap_events_calendar c on c.event_id = m.event_id
			where c.project_id = " . $projectId ." and group_id = ".$dagId." order by c.event_date, c.event_time";
        }
        $query_result = db_query($sql); //TODO use prepared statement...
        $infos = [];
        while ($info = db_fetch_assoc($query_result))
        {
            $infos[]= $info;
        }
        return $infos;
    }

    /**
     * @param $event
     * @return \Eluceo\iCal\Component\Event
     * @throws Exception
     */
    private function createEventFromRecord($event)
    {
        $vEvent = new \Eluceo\iCal\Component\Event();
        if ($event["record"] !== null) {
            $vEvent->setSummary($event["notes"] . " for record " . $event["record"]);
        } else {
            $vEvent->setSummary($event["notes"]);
        }
        $dateTime = new \DateTime($event["event_date"]);
        if ($event["event_time"] !== null) {
            list($hour, $minute) = explode(":", $event["event_time"]);
            $dateTime->setTime($hour, $minute);
        }
        $vEvent->setDtStart($dateTime);
        $vEvent->setDuration(new \DateInterval("PT1H"));
        return $vEvent;
    }
}