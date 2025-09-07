TASK ASSIGNED

Hello {{ $assignedTo->first_name }},

You have been assigned a new task!

TASK DETAILS:
- Title: {{ $task->title }}
- Project: {{ $project->name }}
- Assigned by: {{ $assignedBy->first_name }} {{ $assignedBy->last_name }}
- Status: {{ ucfirst($task->status->value) }}
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
