BEGIN:VCALENDAR
PRODID:-//Elastix Development Department// Elastix 2.0 //EN
VERSION:2.0
{foreach from=$eventlist key=idx item=event}
BEGIN:VEVENT
DTSTAMP:{$event->icalstart}
CREATED:{$event->icalstart}
UID:{$idx}-{$event->id}
SUMMARY:{$event->title}
CLASS:PUBLIC
PRIORITY:5
DTSTART:{$event->icalstart}
DTEND:{$event->icalend}
TRANSP:OPAQUE
SEQUENCE=0
END:VEVENT
{/foreach}
END:VCALENDAR
