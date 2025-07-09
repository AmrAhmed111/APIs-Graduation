# APIs-Graduation
HealthMate – Hospital Management System (Back-End)

**HealthMate** is a full-stack Integrated Hospital Management System designed to revolutionize modern healthcare operations through a unified, secure, and scalable platform. This repository documents the **back-end implementation**, built using **Laravel** and integrated with **AI services** for predictive diagnostics.

---

## 🏥 Project Overview

HealthMate back-end provides robust RESTful APIs to manage hospital operations including:

- Patient registration and management
- Doctor scheduling and appointments
- AI-assisted heart disease predictions
- Medical test bookings and results management
- Secure user authentication and password reset
- Payment processing via Paypal
- Emergency request handling

The API supports both mobile and web clients and is secured with authentication middleware.

---

## 🧰 Tech Stack

| Component           | Technology                  |
|--------------------|------------------------------|
| Language           | PHP (Laravel)                |
| Framework          | Laravel 11                   |
| Database           | MySQL                        |
| API Client         | Postman                      |
| Dev Environment    | Laragon                      |
| Authentication     | Laravel Sanctum / Bearer Auth|

---

## 📁 Project Structure

### backend
- app
  - Http
      - Controllers
      - Middleware
      - Models
- routes
  - api.php
- databas
  - migrations
- public
- storage
- .env
- composer.json

---

## 🔐 Authentication & Security

- Role-based access (admin, doctor, patient)
- Token-based authentication using Laravel Sanctum
- Password reset via secure OTP email system
- Encrypted password storage (bcrypt)
- Middleware protection for sensitive endpoints
- HTTPS and CORS configurations

---

## 📦 Key API Endpoints

| Feature                     | Endpoint                             | Method |
|-----------------------------|--------------------------------------|--------|
| User Registration           | `/api/register`                      | POST   |
| User Login                  | `/api/login`                         | POST   |
| Doctor Registration         | `/api/doctor/register`               | POST   |
| Fetch All Doctors           | `/api/doctors`                       | GET    |
| Book Doctor Appointment     | `/api/appointments/book`             | POST   |
| Book Lab Test               | `/api/lab-tests/appoint`             | POST   |
| Upload Lab Test Result      | `/api/lab-results/upload`            | POST   |
| Request Password Reset Code | `/api/reset-password/request`        | POST   |
| Confirm Password Reset      | `/api/reset-password/confirm`        | POST   |

All endpoints follow RESTful standards and return JSON responses.
