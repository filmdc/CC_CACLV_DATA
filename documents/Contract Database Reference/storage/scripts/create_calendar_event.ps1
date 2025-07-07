param (
    [string]$subject,
    [string]$description,
    [string]$dueDateTime,
    [string]$endDateTime
)

# Connect to Microsoft Graph
Connect-MgGraph -AppId "565e8809-7a50-4849-836b-505fef96edd0" -CertificateThumbprint "43094F9A81D01ED7171678C41A776EE76F8674B4" -TenantId "e6a62f88-5eeb-4675-9e8b-a7bfb2d304d4"

# Define the new event details
$eventDetails = @{
    Subject = "$subject"
    Body = @{
        ContentType = "HTML"
        Content = "$description"
    }
    Start = @{
        DateTime = $dueDateTime->format('Y-m-d')
        TimeZone = "American/New_York"
    }
    End = @{
        DateTime = $endDateTime->format('Y-m-d')
        TimeZone = "American/New_York"
        }
    
    IsAllDay = $true
    # ReminderMinutesBeforeStart = 15
    # ShowAs = "Busy"
}

# Create the new event on the calendar
New-MgUserEvent -UserId "scheduling@communityactionlv.org" -BodyParameter $eventDetails

# Disconnect from Microsoft Graph
Disconnect-MgGraph
