<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Status Updated</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .content { background-color: #ffffff; padding: 20px; border: 1px solid #dee2e6; border-radius: 5px; }
        .project-info { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .status-change { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .old-status { color: #856404; text-decoration: line-through; }
        .new-status { color: #155724; font-weight: bold; }
        .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 12px; color: #6c757d; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Project Status Updated</h1>
            <p>The status of a project you're involved with has been changed.</p>
        </div>

        <div class="content">
            <h2>{{ $project->name }}</h2>

            <div class="status-change">
                <p><strong>Status Change:</strong></p>
                <p>
                    <span class="old-status">{{ ucfirst($oldStatus->value) }}</span>
                    <span style="margin: 0 10px;">→</span>
                    <span class="new-status">{{ ucfirst($newStatus->value) }}</span>
                </p>
                <p><strong>Changed by:</strong> {{ $changedBy->first_name }} {{ $changedBy->last_name }}</p>
            </div>

            <div class="project-info">
                <p><strong>Project:</strong> {{ $project->name }}</p>
                <p><strong>Created by:</strong> {{ $createdBy->first_name }} {{ $createdBy->last_name }}</p>
                @if($project->description)
                <p><strong>Description:</strong> {{ Str::limit($project->description, 150) }}</p>
                @endif
            </div>

            <div class="footer">
                <p>This is an automated notification from the Project Management System.</p>
                <p>Please log in to your account to view the complete project details and any related tasks.</p>
            </div>
        </div>
    </div>
</body>
</html>
