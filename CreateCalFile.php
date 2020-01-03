<?php


class CreateCalFile
{
    public function generate($projectId)
    {
        //Select the data from the database.
        $events = $this->getCalEvents($projectId);
        $vCalendar = new \Eluceo\iCal\Component\Calendar($projectId);
        foreach($events as $event)
        {
            $vEvent = new \Eluceo\iCal\Component\Event();
            if ( $event["record"] !== null) {
                $vEvent->setSummary($event["notes"] . " for record " . $event["record"]);
            } else {
                $vEvent->setSummary($event["notes"]);
            }
            $dateTime = new \DateTime($event["event_date"]);
            if ($event["event_time"] !== null)
            {
                list($hour, $minute) = explode(":", $event["event_time"]);
                $dateTime->setTime($hour, $minute);
            }
            $vEvent->setDtStart($dateTime);
            $vEvent->setDuration(new \DateInterval("PT1H"));
            $vCalendar->addComponent($vEvent);
        }
        return $vCalendar->render();
    }


    public function getCalEvents($projectId)
    {
        $sql = "select * from redcap_events_metadata m right outer join redcap_events_calendar c on c.event_id = m.event_id
			where c.project_id = " . $projectId ." order by c.event_date, c.event_time";
        $query_result = db_query($sql);
        $infos = [];
        while ($info = db_fetch_assoc($query_result))
        {
            $infos[]= $info;
        }

        // Return the two arrays
        return $infos;
    }
}