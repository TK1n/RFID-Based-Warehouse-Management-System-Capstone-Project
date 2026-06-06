# RFID-Based-Warehouse-Management-System-Capstone-Project
This capstone project presents the design and implementation of an automated **Laboratory Equipment Management System** using **Ultra-High Frequency (UHF) RFID** technology. The system aims to solve the inefficiencies, data inaccuracies, and time-consuming processes of traditional manual lab equipment management in universities.

The solution is built based on the **5-Layer IoT Reference Model** (Things, Connect, Collect, Learn, Do) to ensure scalability, real-time performance, and reliability.

## Key Objectives

- Replace manual inventory and borrowing processes with automated RFID-based tracking.
- Develop a robust Edge Gateway application using C# with Finite State Machine (FSM) for autonomous scanning.
- Build a user-friendly web platform for students and lab managers.
- Implement "Quick Borrow" feature to streamline equipment borrowing.
- Evaluate system performance in real laboratory environments.

## Technical Implementation

The project integrates hardware and software components across the full IoT stack:

- **UHF RFID Hardware**: SM06 UHF Reader + passive EPC Gen2 RFID tags (ISO 18000-63).
- **Edge Application**: C# (.NET Framework) with custom Finite State Machine and File-based IPC. [Github Link](https://github.com/Marvick-Lee/Manager_Reader_App_Publish)
- **Backend**: PHP + PostgreSQL (Amazon RDS).
- **Frontend**: Modern web interface with JavaScript for real-time dashboard and search.
- **Communication**: Asynchronous File-based IPC between Reader App and Web Backend.
- **Notifications**: Automated email alerts via PHPMailer.

**Key Technical Highlights**:
- Automated inventory scanning every 10 minutes without human intervention.
- Robust data de-duplication and buffering mechanism.
- Real-time equipment status updates.

## Features

- Real-time equipment availability dashboard.
- Advanced search and filtering by location/device type.
- **Quick Borrow** – one-click borrowing process.
- Automated borrow/return tracking via RFID scanning.
- Transaction history and management tools for lab managers.
- Email notifications for borrowing/returning.
- User authentication and role-based access.

## Demo Video
[Youtube Link](https://www.youtube.com/watch?v=c9gG1iJ7aqg)
