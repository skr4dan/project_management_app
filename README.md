# Project Management App

A comprehensive Laravel 12-based project management application with JWT authentication, role-based access control, and RESTful API endpoints for managing projects, tasks, and users.

## ✅ Quality Assurance Status

- **✅ PHPStan Analysis**: No errors (Level 8 - Maximum Strictness)
- **✅ Tests**: All 315 tests passing (1,349 assertions in 6.02s)

## 📋 Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL or SQLite database

## ⚙️ Installation and Setup

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd project-management-app
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Environment Configuration:**
   ```bash
   cp .env.example .env
   ```

   Update the `.env` file with your database credentials and other settings:
   ```env
   APP_NAME="Project Management App"
   APP_URL=http://localhost:8000

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=project_management
   DB_USERNAME=your_username
   DB_PASSWORD=your_password

   JWT_SECRET=your_jwt_secret_key

   # Test Data Passwords (optional - for predictable test user logins)
   DEMO_ADMIN_PASSWORD=admin123
   DEMO_MANAGER_PASSWORD=manager456
   DEMO_USER_PASSWORD=user789
   ```

4. **Generate application key:**
   ```bash
   php artisan key:generate
   ```

5. **Run database migrations:**
   ```bash
   php artisan migrate
   ```

6. **Seed the database with sample data:**
   ```bash
   php artisan db:seed
   ```

7. **Start the development server:**
   ```bash
   php artisan serve
   ```

   The application will be available at `http://localhost:8000`

## 🗄️ Database Setup

### Running Migrations
```bash
php artisan migrate
```

### Seeding Database
```bash
php artisan db:seed
```

### Fresh Migration with Seeding
```bash
php artisan migrate:fresh --seed
```

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

## 📚 API Documentation

### Authentication Endpoints

#### Register User
```http
POST /api/auth/register
```

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "P@ssword123",
  "password_confirmation": "P@ssword123",
  "avatar": "file (optional)"
}
```

#### Login User
```http
POST /api/auth/login
```

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "P@ssword123"
}
```

#### Get User Profile
```http
GET /api/auth/user
Authorization: Bearer {token}
```

#### Logout User
```http
POST /api/auth/logout
Authorization: Bearer {token}
```

#### Refresh Token
```http
POST /api/auth/refresh
Authorization: Bearer {token}
```

### User Management Endpoints

#### List Users (Admin/Manager only)
```http
GET /api/users
Authorization: Bearer {token}
```

#### Get User Details
```http
GET /api/users/{id}
Authorization: Bearer {token}
```

#### Update User
```http
PUT /api/users/{id}
Authorization: Bearer {token}
```

### Project Management Endpoints

#### List Projects
```http
GET /api/projects
Authorization: Bearer {token}
```

#### Create Project (Manager/Admin only)
```http
POST /api/projects
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "name": "New Project",
  "description": "Project description",
  "status": "active"
}
```

#### Get Project Details
```http
GET /api/projects/{id}
Authorization: Bearer {token}
```

#### Update Project
```http
PUT /api/projects/{id}
Authorization: Bearer {token}
```

#### Delete Project
```http
DELETE /api/projects/{id}
Authorization: Bearer {token}
```

### Task Management Endpoints

#### List Tasks
```http
GET /api/tasks
Authorization: Bearer {token}
```

#### Create Task (Manager/Admin only)
```http
POST /api/tasks
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "title": "Task Title",
  "description": "Task description",
  "status": "pending",
  "priority": "medium",
  "project_id": 1,
  "assigned_to": 2,
  "due_date": "2024-12-31"
}
```

#### Get Task Details
```http
GET /api/tasks/{id}
Authorization: Bearer {token}
```

#### Update Task
```http
PUT /api/tasks/{id}
Authorization: Bearer {token}
```

#### Delete Task
```http
DELETE /api/tasks/{id}
Authorization: Bearer {token}
```

### Statistics Endpoints (Admin only)

#### Get Statistics
```http
GET /api/statistics
Authorization: Bearer {token}
```

## 📝 API Response Format

All API responses follow a consistent format:

**Success Response:**
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful"
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Error description"
}
```

## 📋 Postman Collection

A Postman collection file is included in the repository (`postman_collection.json`) with pre-configured requests for all API endpoints. To use it:

1. Import the `postman_collection.json` file into Postman
2. Set the `base_url` variable to your API URL (default: `http://localhost:8000/api`)
3. The collection automatically saves the JWT token after login for authenticated requests

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

### Run Tests with Coverage
```bash
php artisan test --coverage
```

### Run Specific Test File
```bash
php artisan test tests/Feature/AuthTest.php
```

### Run Tests in Parallel
```bash
php artisan test --parallel
```

## 🔧 Development Commands

### Start Development Server
```bash
php artisan serve
```

### Start Frontend Development Server
```bash
npm run dev
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
- **Middleware Protection**: Route protection with JWT and role middleware

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
