TASK STATUS UPDATED

Hello {{ $assignedTo->first_name }},

The status of your assigned task has been changed!

TASK DETAILS:
- Title: {{ $task->title }}
- Project: {{ $project->name }}
- Status: {{ ucfirst($oldStatus->value) }} → {{ ucfirst($newStatus->value) }}
- Changed by: {{ $changedBy->first_name }} {{ $changedBy->last_name }}
- Priority: {{ ucfirst($task->priority->value) }}
@if($task->due_date)
- Due Date: {{ $task->due_date->format('M j, Y') }}
@endif

@if($task->description)
DESCRIPTION:
{{ $task->description }}
@endif

You can view this task at: {{ $notificationUrl }}

If you no longer wish to receive these notifications, you can unsubscribe here: {{ $unsubscribeUrl }}

This is an automated notification from the Project Management System.

Best regards,
{{ config('notifications.email.from_name') }}
