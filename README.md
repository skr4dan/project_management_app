# Project Management App

A comprehensive Laravel 12-based project management application with JWT authentication, role-based access control, and RESTful API endpoints for managing projects, tasks, and users.

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
- **Admin User**: `admin@example.com` / password: `password`
- **Manager Users**: 5 users with manager role
- **Regular Users**: 5 users with user role
- **Sample Projects**: 3 projects with various statuses
- **Sample Tasks**: 20 tasks assigned to different users

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

### Run Code Linting
```bash
./vendor/bin/pint
```

### Run Static Analysis
```bash
./vendor/bin/phpstan analyse
```

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
