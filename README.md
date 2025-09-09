# Project Management App

A Laravel 12-based project management application with JWT authentication, role-based access control, and RESTful API endpoints for managing projects, tasks, and users.

## ✅ Quality Assurance Status

- **✅ PHPStan Analysis**: No errors (Level 8 - Maximum Strictness)
- **✅ Tests**: All 325 tests passing (1393 assertions in 7.08s)

## 📋 Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL or SQLite database

## ⚙️ Installation and Setup
### Quick Setup
1. **Create MySQL database and user:**
   ```sql
   CREATE DATABASE project_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'laravel_user'@'localhost' IDENTIFIED BY 'password';
   GRANT ALL PRIVILEGES ON project_management.* TO 'laravel_user'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   ```

2. **Run this single command For a quick setup:**
   ```bash
   git clone https://github.com/skr4dan/project_management_app \
      && cd project_management_app \
      && composer install \
      && cp .env.example .env \
      && echo -e "\n# Test Data Passwords (optional - for predictable test user logins)\nDEMO_ADMIN_PASSWORD=admin123\nDEMO_MANAGER_PASSWORD=manager456\nDEMO_USER_PASSWORD=user789" >> .env \
      && php artisan key:generate \
      && php artisan jwt:secret \
      && php artisan migrate \
      && php artisan db:seed \
      && php artisan serve
   ```
## 🗄️ Database

### Available Seed Data

#### Test Users
- **Admin User**: `admin@example.com` / password: `admin123`
- **Manager User**: `manager@example.com` / password: `manager456`
- **Regular User**: `user@example.com` / password: `user789`
- **Additional Manager Users**: 5 randomly generated users with manager role
- **Additional Regular Users**: 5 randomly generated users with user role

#### Sample Data
- **Projects**: 7 projects with various statuses (active, completed, on-hold)
- **Tasks**: 50 tasks distributed across projects and assigned to different users


## 📋 Postman Collection

A Postman collection file is included in the repository (`postman_collection.json`) with pre-configured requests for all API endpoints. To use it:

1. Import the `postman_collection.json` file into Postman
2. Set the `base_url` variable to your API URL (default: `http://localhost:8000/api`)
3. The collection automatically saves the JWT token after login for authenticated requests


## 📚 API Documentation

### Authentication Endpoints

#### Register User
```http
POST /api/auth/register
```

**Request Body:**
```json
{
  "name": "Jane Smith",
  "email": "jane.smith@example.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!",
  "avatar": "(Upload a JPEG/PNG file)"
}
```

**Success Response (201):**
```json
{
  "success": true,
  "data": {
    "access_token": "jwt_token_string",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com",
      "role": {
        "id": 1,
        "slug": "user",
        "name": "User"
      },
      "status": "active",
      "avatar": "http://localhost:8000/storage/avatars/avatar.jpg",
      "phone": null,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  },
  "message": "Registration successful"
}
```

#### Login User
```http
POST /api/auth/login
```

**Request Body:**
```json
{
  "email": "jane.smith@example.com",
  "password": "SecurePass123!"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "access_token": "jwt_token_string",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com",
      "role": {
        "id": 1,
        "slug": "user",
        "name": "User"
      },
      "status": "active",
      "avatar": "http://localhost:8000/storage/avatars/avatar.jpg",
      "phone": null,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  },
  "message": "Login successful"
}
```

#### Get User Profile
```http
GET /api/auth/user
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "role": {
      "id": 1,
      "slug": "user",
      "name": "User"
    },
    "status": "active",
    "avatar": "http://localhost:8000/storage/avatars/avatar.jpg",
    "phone": null,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  },
  "message": "User profile retrieved successfully"
}
```

#### Logout User
```http
POST /api/auth/logout
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Logout successful"
}
```

#### Refresh Token
```http
POST /api/auth/refresh
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "access_token": "new_jwt_token_string",
    "token_type": "Bearer",
    "expires_in": 3600
  },
  "message": "Token refreshed successfully"
}
```

### User Management Endpoints

#### List Users (Admin/Manager only)
```http
GET /api/users
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com",
      "role": {
        "id": 1,
        "slug": "user",
        "name": "User"
      },
      "status": "active",
      "avatar": "http://localhost:8000/storage/avatars/avatar.jpg",
      "phone": "+1234567890",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "message": "Users retrieved successfully"
}
```

#### Get User Details
```http
GET /api/users/{id}
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "role": {
      "id": 1,
      "slug": "user",
      "name": "User"
    },
    "status": "active",
    "avatar": "http://localhost:8000/storage/avatars/avatar.jpg",
    "phone": "+1234567890",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  },
  "message": "User retrieved successfully"
}
```

#### Update User
```http
PUT /api/users/{id}
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "first_name": "Jane",
  "last_name": "Johnson",
  "email": "jane.johnson@example.com",
  "phone": "+1-555-0123",
  "avatar": "(Upload a JPEG/PNG file)"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Smith",
    "email": "john.smith@example.com",
    "role": {
      "id": 1,
      "slug": "user",
      "name": "User"
    },
    "status": "active",
    "avatar": "http://localhost:8000/storage/avatars/avatar.jpg",
    "phone": "+1234567890",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  },
  "message": "User updated successfully"
}
```

### Project Management Endpoints

#### List Projects
```http
GET /api/projects?status={active|completed|archived}
Authorization: Bearer {token}
```

**Query Parameters:**
- `status` (optional): Filter by project status (`active`, `completed`, `archived`)

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Website Redesign",
      "description": "Complete overhaul of company website",
      "status": "active",
      "created_by": {
        "id": 2,
        "first_name": "Jane",
        "last_name": "Manager",
        "email": "jane@example.com"
      },
      "tasks_count": 5,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "message": "Projects retrieved successfully"
}
```

#### Create Project (Manager/Admin only)
```http
POST /api/projects
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "name": "Website Redesign Project",
  "description": "Complete overhaul of company website with modern design and improved user experience"
}
```

**Success Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "New Project",
    "description": "Project description",
    "status": "active",
    "created_by": {
      "id": 2,
      "first_name": "Jane",
      "last_name": "Manager",
      "email": "jane@example.com"
    },
    "tasks_count": 0,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  },
  "message": "Project created successfully"
}
```

#### Get Project Details
```http
GET /api/projects/{id}
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Website Redesign",
    "description": "Complete overhaul of company website",
    "status": "active",
    "created_by": {
      "id": 2,
      "first_name": "Jane",
      "last_name": "Manager",
      "email": "jane@example.com"
    },
    "tasks_count": 5,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  },
  "message": "Project retrieved successfully"
}
```

#### Update Project
```http
PUT /api/projects/{id}
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "name": "Updated Website Redesign Project",
  "description": "Updated project description with new requirements",
  "status": "completed"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Updated Project Name",
    "description": "Updated project description",
    "status": "completed",
    "created_by": {
      "id": 2,
      "first_name": "Jane",
      "last_name": "Manager",
      "email": "jane@example.com"
    },
    "tasks_count": 5,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-02T00:00:00.000000Z"
  },
  "message": "Project updated successfully"
}
```

#### Delete Project
```http
DELETE /api/projects/{id}
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Project deleted successfully"
}
```

### Task Management Endpoints

#### List Tasks
```http
GET /api/tasks?status={pending|in_progress|completed}&priority={low|medium|high}&project_id={id}&assigned_to={id}&sort_by={due_date|created_at}&sort_order={asc|desc}&per_page={15}&page={1}
Authorization: Bearer {token}
```

**Query Parameters:**
- `status` (optional): Filter by task status (`pending`, `in_progress`, `completed`)
- `priority` (optional): Filter by task priority (`low`, `medium`, `high`)
- `project_id` (optional): Filter by project ID
- `assigned_to` (optional): Filter by assigned user ID
- `sort_by` (optional): Sort by field (`due_date`, `created_at`)
- `sort_order` (optional): Sort order (`asc`, `desc`)
- `per_page` (optional): Items per page (default: 15)
- `page` (optional): Page number (default: 1)

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Design homepage mockup",
      "description": "Create wireframes and mockups for the new homepage",
      "status": "in_progress",
      "priority": "high",
      "project": {
        "id": 1,
        "name": "Website Redesign"
      },
      "assigned_to": {
        "id": 3,
        "first_name": "Bob",
        "last_name": "Designer",
        "email": "bob@example.com"
      },
      "created_by": {
        "id": 2,
        "first_name": "Jane",
        "last_name": "Manager",
        "email": "jane@example.com"
      },
      "due_date": "2024-02-01T00:00:00.000000Z",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-02T00:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 50,
    "last_page": 4,
    "from": 1,
    "to": 15
  },
  "message": "Tasks retrieved successfully"
}
```

#### Create Task (Manager/Admin only)
```http
POST /api/tasks
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "title": "Design homepage mockup",
  "description": "Create wireframes and mockups for the new homepage layout",
  "priority": "high",
  "project_id": 1,
  "assigned_to": 3,
  "due_date": "2024-02-15"
}
```

**Success Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Design homepage mockup",
    "description": "Create wireframes and mockups for the new homepage",
    "status": "pending",
    "priority": "medium",
    "project": {
      "id": 1,
      "name": "Website Redesign"
    },
    "assigned_to": {
      "id": 3,
      "first_name": "Bob",
      "last_name": "Designer",
      "email": "bob@example.com"
    },
    "created_by": {
      "id": 2,
      "first_name": "Jane",
      "last_name": "Manager",
      "email": "jane@example.com"
    },
    "due_date": "2024-02-01T00:00:00.000000Z",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  },
  "message": "Task created successfully"
}
```

#### Get Task Details
```http
GET /api/tasks/{id}
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Design homepage mockup",
    "description": "Create wireframes and mockups for the new homepage",
    "status": "in_progress",
    "priority": "high",
    "project": {
      "id": 1,
      "name": "Website Redesign"
    },
    "assigned_to": {
      "id": 3,
      "first_name": "Bob",
      "last_name": "Designer",
      "email": "bob@example.com"
    },
    "created_by": {
      "id": 2,
      "first_name": "Jane",
      "last_name": "Manager",
      "email": "jane@example.com"
    },
    "due_date": "2024-02-01T00:00:00.000000Z",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-02T00:00:00.000000Z"
  },
  "message": "Task retrieved successfully"
}
```

#### Update Task
```http
PUT /api/tasks/{id}
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "title": "Updated homepage design task",
  "description": "Updated task with new design specifications",
  "status": "completed",
  "priority": "high",
  "assigned_to": 3,
  "due_date": "2024-02-20"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Updated task title",
    "description": "Updated task description",
    "status": "completed",
    "priority": "high",
    "project": {
      "id": 1,
      "name": "Website Redesign"
    },
    "assigned_to": {
      "id": 3,
      "first_name": "Bob",
      "last_name": "Designer",
      "email": "bob@example.com"
    },
    "created_by": {
      "id": 2,
      "first_name": "Jane",
      "last_name": "Manager",
      "email": "jane@example.com"
    },
    "due_date": "2024-02-01T00:00:00.000000Z",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-03T00:00:00.000000Z"
  },
  "message": "Task updated successfully"
}
```

#### Delete Task
```http
DELETE /api/tasks/{id}
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Task deleted successfully"
}
```

### Statistics Endpoints (Admin only)

#### Get Statistics
```http
GET /api/statistics
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "total_users": 25,
    "total_projects": 7,
    "total_tasks": 50,
    "projects_by_status": {
      "active": 4,
      "completed": 2,
      "archived": 1
    },
    "tasks_by_status": {
      "pending": 15,
      "in_progress": 20,
      "completed": 15
    },
    "tasks_by_priority": {
      "low": 10,
      "medium": 25,
      "high": 15
    },
    "users_by_role": {
      "admin": 1,
      "manager": 6,
      "user": 18
    },
    "recent_activity": {
      "projects_created_last_30_days": 2,
      "tasks_completed_last_7_days": 8,
      "new_users_last_30_days": 3
    }
  },
  "message": "Statistics retrieved successfully"
}
```

## 📝 API Response Format

All API responses follow a consistent format defined by the `JsonResponse` class. The API supports multiple response types:

### Success Responses

**Standard Success Response (200):**
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful"
}
```

**Success Response with Pagination (200):**
```json
{
  "success": true,
  "data": [ ... ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 50,
    "last_page": 4,
    "from": 1,
    "to": 15
  },
  "message": "Resources retrieved successfully"
}
```

**Created Response (201):**
```json
{
  "success": true,
  "data": { ... },
  "message": "Resource created successfully"
}
```

**Success Response without Data (200):**
```json
{
  "success": true,
  "message": "Operation completed successfully"
}
```

### Error Responses

**Bad Request (400):**
```json
{
  "success": false,
  "message": "Validation failed or bad request data"
}
```

**Unauthorized (401):**
```json
{
  "success": false,
  "message": "Authentication required"
}
```

**Forbidden (403):**
```json
{
  "success": false,
  "message": "Access denied. Insufficient permissions."
}
```

**Not Found (404):**
```json
{
  "success": false,
  "message": "Resource not found"
}
```

**Internal Server Error (500):**
```json
{
  "success": false,
  "message": "Internal server error occurred"
}
```

### Response Structure Details

- **`success`**: Boolean indicating if the request was successful
- **`data`**: The actual response data (omitted for simple success messages)
- **`message`**: Human-readable message describing the result
- **`pagination`**: Present only for paginated responses with metadata

All responses include appropriate HTTP status codes and consistent JSON structure for easy client-side handling.

## 🧪 Testing

This project includes **315 comprehensive individual tests** covering various aspects of the application:

- **Feature Tests** (133 tests across 9 files):
  - **AuthTest.php**: 22 authentication tests (registration, login, profile, token management)
  - **TaskApiTest.php**: 35 task API tests (CRUD operations, validation, permissions)
  - **UserApiTest.php**: 22 user API tests (user management, permissions)
  - **StatisticsApiTest.php**: 7 statistics API tests (admin access, data integrity)
  - **ProjectApiTest.php**: 23 project API tests (CRUD operations, status changes)
  - **Notification System Tests**: 24 tests across 4 files covering task assignments, status changes, and project updates

- **Unit Tests** (182 tests across 17 files):
  - **Repository Tests**: 93 tests across 4 files (User, Task, Project, Role repositories)
  - **Event Tests**: 17 tests across 3 files (TaskAssigned, TaskStatusChanged, ProjectStatusChanged)
  - **Job Tests**: 20 tests across 3 files (notification jobs for tasks and projects)
  - **Listener Tests**: 15 tests across 3 files (notification listeners)
  - **Mail Tests**: 18 tests across 3 files (email notifications)
  - **AuthServiceTest.php**: 19 authentication service tests

### Run All Tests
```bash
php artisan test
```

## 🔧 Development Commands

### Start Development Server
```bash
php artisan serve
```

### Code Quality & Analysis

#### Run Code Style Fixing (Laravel Pint)
```bash
./vendor/bin/pint
```

This project uses **Laravel Pint** with the Laravel preset to automatically fix code style issues and maintain consistent formatting across the codebase.

#### Run Static Analysis (PHPStan)
```bash
./vendor/bin/phpstan analyse
```

The project is configured with **PHPStan at level 8** (maximum strictness) for comprehensive static analysis coverage of the `app/` and `database/` directories, ensuring high code quality and catching potential issues early.

## 📁 Project Structure

```
app/
├── DTOs/                 # Data Transfer Objects
├── Enums/                # Enumeration classes
├── Http/
│   ├── Controllers/      # API Controllers
│   ├── Middleware/       # Custom middleware
│   ├── Requests/         # Form request validation
│   └── Resources/        # API Resources
├── Models/               # Eloquent models
├── Repositories/         # Repository pattern implementation
├── Services/             # Business logic services
└── Providers/            # Service providers

database/
├── factories/            # Model factories
├── migrations/           # Database migrations
└── seeders/              # Database seeders

tests/
├── Feature/              # Feature tests
└── Unit/                 # Unit tests
```

## 🔐 Authentication & Authorization

The application uses JWT (JSON Web Tokens) for authentication with the following features:

- **Token Expiration**: Tokens expire after 60 minutes
- **Token Refresh**: Automatic token refresh capability
- **Role-Based Access**: Three user roles (admin, manager, user)
- **Permission-based Protection**: Route protection with JWT and roles' permissions

### User Roles and Permissions

1. **Admin**:
   - Full access to all endpoints
   - User management
   - Statistics access
   - Project and task management

2. **Manager**:
   - Project and task creation
   - User listing
   - Limited user management

3. **User**:
   - View assigned tasks and projects
   - Update own profile
   - Limited read access
