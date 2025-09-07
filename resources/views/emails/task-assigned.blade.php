<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Assigned</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .content { background-color: #ffffff; padding: 20px; border: 1px solid #dee2e6; border-radius: 5px; }
        .task-info { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .priority { display: inline-block; padding: 5px 10px; border-radius: 3px; color: white; font-size: 12px; }
        .priority-low { background-color: #28a745; }
        .priority-medium { background-color: #ffc107; color: #000; }
        .priority-high { background-color: #dc3545; }
        .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 12px; color: #6c757d; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Task Assigned</h1>
            <p>You have been assigned a new task!</p>
        </div>

        <div class="content">
            <h2>{{ $task->title }}</h2>

            <div class="task-info">
                <p><strong>Project:</strong> {{ $project->name }}</p>
                <p><strong>Assigned by:</strong> {{ $assignedBy->first_name }} {{ $assignedBy->last_name }}</p>
                <p><strong>Status:</strong> {{ ucfirst($task->status->value) }}</p>
                <p><strong>Priority:</strong>
                    <span class="priority priority-{{ strtolower($task->priority->value) }}">
                        {{ ucfirst($task->priority->value) }}
                    </span>
                </p>
                @if($task->due_date)
                <p><strong>Due Date:</strong> {{ $task->due_date->format('M j, Y') }}</p>
                @endif
            </div>

            @if($task->description)
            <div>
                <h3>Description:</h3>
                <p>{{ $task->description }}</p>
            </div>
            @endif

            <div class="footer">
                <p>This is an automated notification from the Project Management System.</p>
                <p>Please log in to your account to view the complete task details and update your progress.</p>
            </div>
        </div>
    </div>
</body>
</html>
