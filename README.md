# LocalLink 🛍️

> A local C2C marketplace designed to make informal buying and selling within local communities safer, simpler, and more convenient.

LocalLink is a Consumer-to-Consumer (C2C) e-commerce platform that allows individuals to buy and sell products within their local community. The platform provides users with a central marketplace for discovering products, communicating with sellers, managing listings, and completing transactions.

The system was designed with a strong focus on local commerce, user interaction, transaction security, and trust between buyers and sellers.

---

## 📌 Project Overview

Traditional informal buying and selling often takes place through social media platforms or messaging applications. While these platforms make it easy to advertise products, they provide limited functionality for managing listings, communicating with buyers, tracking transactions, and resolving disputes.

LocalLink addresses these challenges by providing a dedicated C2C marketplace where users can:

* Browse locally listed products
* Search and filter marketplace listings
* Create and manage product listings
* Communicate directly with other users
* Leave reviews
* Manage their user profile
* Complete transactions through a simulated escrow system
* Raise disputes when transaction problems occur

The platform also provides administrative functionality for managing users, products, categories and disputed transactions.

---

## ✨ Features

### 🛒 Marketplace

* Browse available products
* Search for products
* Filter listings by location
* Sort products by:

  * Newest
  * Price: Low to High
  * Price: High to Low
* Browse products by category
* View individual product details
* Display product images and seller information

### 👤 User Accounts

* User registration and login
* Session-based authentication
* User profiles
* Profile information management
* Profile picture uploads
* User dashboard
* View personal listings
* View transaction information
* Secure logout

### 🏷️ Product Listings

Authenticated users can act as sellers and:

* Create product listings
* Upload product images
* Select product categories
* Add product descriptions
* Set prices
* Specify listing locations
* Edit existing listings
* Delete listings
* Manage listing status

### 💬 Messaging

LocalLink includes an internal messaging system allowing users to communicate with each other.

Features include:

* User-to-user conversations
* Sending messages
* Retrieving conversations
* Retrieving individual messages
* Unread message tracking
* Unread-message notifications

### ⭐ Reviews

Users can provide feedback through product/user reviews.

The review system provides functionality for:

* Adding reviews
* Retrieving reviews
* Displaying user feedback

### 💳 Escrow Transactions

LocalLink includes a **simulated escrow payment system** designed to demonstrate how a safer C2C transaction workflow could operate.

The escrow system supports:

1. Starting a transaction
2. Simulating payment
3. Funding the transaction
4. Tracking transaction status
5. Generating a release PIN
6. Buyer confirmation
7. Completing the transaction
8. Raising disputes
9. Administrative dispute resolution
10. Releasing funds to the seller or refunding the buyer

The transaction flow follows states such as:

pending
   ↓
funded
   ↓
in_progress
   ↓
completed


Disputed transactions follow an alternative workflow:

pending
   ↓
funded
   ↓
in_progress
   ↓
disputed
   ↓
resolved
   - released to seller
   - refunded to buyer


> **Note:** The payment gateway is simulated and does not process real financial transactions yet it is implemented in a way that PayFast can easily be added.

### 🛡️ Administration

Administrators have access to a dedicated administration area for managing the platform.

Administrative functionality includes:

* User management
* Product management
* Category management
* Escrow transaction management
* Dispute resolution
* Transaction monitoring

---

## 🏗️ System Architecture

LocalLink follows a structured PHP web application architecture that separates the public interface, API functionality, configuration and reusable application components.

                    ┌─────────────────────┐
                    │       User          │
                    └──────────┬──────────┘
                               │
                               ▼
                    ┌─────────────────────┐
                    │   Public Frontend   │
                    │   PHP / HTML / CSS  │
                    │   JavaScript / jQuery│
                    └──────────┬──────────┘
                               │
                               ▼
                    ┌─────────────────────┐
                    │      API Layer      │
                    │ Authentication      │
                    │ Products            │
                    │ Categories          │
                    │ Messages            │
                    │ Reviews             │
                    │ User Management     │
                    └──────────┬──────────┘
                               │
                               ▼
                    ┌─────────────────────┐
                    │        PDO          │
                    │ Database Connection  │
                    └──────────┬──────────┘
                               │
                               ▼
                    ┌─────────────────────┐
                    │       MySQL         │
                    │     Database        │
                    └─────────────────────┘
                    

The application also contains separate seller and administrator areas for role-specific functionality.

---

## 🧰 Technology Stack

| Technology      | Purpose                                    |
| --------------- | ------------------------------------------ |
| **PHP**         | Server-side application logic              |
| **MySQL**       | Database management and persistent storage |
| **PDO**         | Secure database connectivity               |
| **HTML5**       | Page structure                             |
| **CSS3**        | Styling and responsive layouts             |
| **JavaScript**  | Client-side functionality                  |
| **jQuery**      | AJAX requests and frontend interactions    |
| **Bootstrap 5** | Responsive UI components                   |
| **Git**         | Version control                            |
| **GitHub**      | Source-code management                     |

The application uses PDO with prepared statements and disables emulated prepares for database operations.

---

## 📁 Project Structure

LocalLink-Project/
│
├── admin/
│   ├── assets/
│   ├── categories.php
│   ├── dashboard.php
│   ├── escrow_panel.php
│   ├── index.php
│   ├── products.php
│   └── users.php
│
├── api/
│   ├── auth/
│   │   ├── login.php
│   │   └── register.php
│   │
│   ├── categories/
│   │   ├── add-category.php
│   │   ├── delete-category.php
│   │   ├── edit-category.php
│   │   └── get-categories.php
│   │
│   ├── messages/
│   │   ├── get-conversations.php
│   │   ├── get-messages.php
│   │   ├── get-unread-count.php
│   │   └── send-message.php
│   │
│   ├── products/
│   │   ├── add-product.php
│   │   ├── delete-product.php
│   │   ├── edit-product.php
│   │   └── get-product.php
│   │
│   ├── reviews/
│   │   ├── add-review.php
│   │   └── get-reviews.php
│   │
│   └── user/
│       ├── get-dashboard.php
│       └── update-profile.php
│
├── config/
│   └── config.php
│
├── includes/
│   ├── auth.php
│   ├── categories.php
│   ├── db.php
│   ├── help-model.php
│   └── helpers.php
│
├── public/
│   ├── assets/
│   │   ├── css/
│   │   ├── images/
│   │   └── js/
│   │
│   ├── escrow/
│   │   ├── confirm.php
│   │   ├── dispute.php
│   │   ├── initiate.php
│   │   ├── mock_payment.php
│   │   ├── my-transactions.php
│   │   └── status.php
│   │
│   ├── seller/
│   │   ├── add-product.php
│   │   └── edit-product.php
│   │
│   ├── dashboard.php
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   ├── messages.php
│   └── product.php
│
├── ESCROW_README.md
└── README.md

## 🔐 Security Considerations

Several security mechanisms are incorporated into the application, including:

* Session-based authentication
* Role-based access control
* PDO prepared statements
* Input validation
* Sanitisation of displayed user data
* Restricted access to administrative functionality
* Buyer/seller transaction access control
* Escrow PIN verification
* Database transactions for important escrow operations

The escrow implementation specifically uses prepared statements, session authentication, access control and database transactions to help maintain data integrity.

---

## 💰 Escrow System

LocalLink's escrow functionality is a simulation for the purposes of the project.

The system demonstrates how an online marketplace could introduce an intermediary transaction process between buyers and sellers.

The simulated system includes:

* Transaction creation
* Platform fee calculation
* Simulated payment
* Payment references
* Release PIN generation
* Transaction status tracking
* Buyer confirmation
* Dispute creation
* Administrative resolution
* Seller release
* Buyer refund
* Transaction history

Platform fees are currently simulated according to the following rules:

| Transaction Amount | Platform Fee |
| ------------------ | -----------: |
| Under R500         |           R5 |
| R500 and above     |           2% |

---

## 📱 Responsive Design

The frontend includes responsive styling intended to support different screen sizes.

The application uses:

* Bootstrap 5
* Custom CSS
* Mobile-specific styling
* Responsive layouts
* Mobile navigation components

The marketplace interface provides filtering, category navigation, product grids and mobile navigation functionality.

---

## 🧪 Project Status

**Current Version:** `1.0.0`

LocalLink is currently a **working academic C2C marketplace project**.

The application contains implemented functionality for:

* Authentication
* Marketplace listings
* Product management
* Categories
* User profiles
* Messaging
* Reviews
* Seller functionality
* Administration
* Escrow transaction simulation
* Dispute handling

The version number is currently defined as `1.0.0` in the application's configuration.

---

## 🔮 Future Improvements

Potential future improvements include:

* Integration with a real payment gateway (e.g. PayFast)
* Email notifications
* SMS transaction notifications
* Improved search functionality
* Advanced product recommendations
* More comprehensive seller ratings
* Improved notification functionality
* Production deployment
* Automated testing
* Improved database migration/setup scripts
* Additional security hardening
* Image optimisation and cloud storage
* More advanced analytics for sellers and administrators

The current escrow documentation also identifies real payment-gateway integration, email notifications and SMS alerts as possible future improvements.

---

## 🎓 Academic Project

LocalLink was developed as a software engineering project to demonstrate the design and implementation of a C2C e-commerce platform.

The project demonstrates practical application of:

* Web application development
* Database-driven application design
* Client-server architecture
* REST-style API endpoints
* Authentication and authorisation
* CRUD operations
* Session management
* Database security
* User interface development
* Transaction management
* Software architecture
* Version control with Git and GitHub

---

## 👨‍💻 Author

**Ivan**

GitHub: [@Ivan-2002](https://github.com/Ivan-2002)

---

## 📄 License

This project was developed as an academic software engineering project.

Unless otherwise stated, the code is provided for educational and portfolio purposes.
 
