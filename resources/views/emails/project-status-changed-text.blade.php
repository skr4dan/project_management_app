PROJECT STATUS UPDATED

Hello {{ $recipient->first_name }},

The status of a project you're involved with has been changed!

PROJECT DETAILS:
- Name: {{ $project->name }}
- Status: {{ ucfirst($oldStatus->value) }} → {{ ucfirst($newStatus->value) }}
- Changed by: {{ $changedBy->first_name }} {{ $changedBy->last_name }}
- Created by: {{ $createdBy->first_name }} {{ $createdBy->last_name }}

@if($project->description)
DESCRIPTION:
{{ $project->description }}
@endif

You can view this project at: {{ $notificationUrl }}

If you no longer wish to receive these notifications, you can unsubscribe here: {{ $unsubscribeUrl }}

This is an automated notification from the Project Management System.

Best regards,
{{ config('notifications.email.from_name') }}
