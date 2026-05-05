<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratory Policies</title>
    <style>
        /* Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header Styles */
        header {
            background: linear-gradient(135deg, #2c3e50, #4a6491);
            color: white;
            padding: 30px 0;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        /* Navigation */
        .nav-container {
            background-color: white;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        nav ul {
            display: flex;
            list-style: none;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        nav li {
            margin: 0;
        }
        
        nav a {
            display: block;
            padding: 15px 20px;
            text-decoration: none;
            color: #2c3e50;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        nav a:hover {
            background-color: #f0f4f8;
            color: #3498db;
        }
        
        /* Main Content */
        main {
            padding: 40px 0;
        }
        
        .policy-section {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }
        
        .policy-section:hover {
            transform: translateY(-5px);
        }
        
        .policy-section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eaecef;
            display: flex;
            align-items: center;
        }
        
        .policy-section h2 i {
            margin-right: 10px;
            color: #3498db;
        }
        
        .policy-section h3 {
            color: #3498db;
            margin: 20px 0 10px;
        }
        
        .policy-section ul, .policy-section ol {
            margin-left: 20px;
            margin-bottom: 15px;
        }
        
        .policy-section li {
            margin-bottom: 8px;
        }
        
        .warning {
            background-color: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 4px 4px 0;
        }
        
        .important {
            background-color: #e8f4fd;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 4px 4px 0;
        }
        
        /* Footer */
        footer {
            background-color: #2c3e50;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 40px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            header h1 {
                font-size: 2rem;
            }
            
            nav ul {
                flex-direction: column;
            }
            
            nav a {
                padding: 12px 15px;
                text-align: center;
            }
            
            .policy-section {
                padding: 20px;
            }
        }
        
        /* Print Styles */
        @media print {
            .nav-container, footer {
                display: none;
            }
            
            .policy-section {
                box-shadow: none;
                border: 1px solid #ddd;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Laboratory Policies & Procedures</h1>
            <p>Ensuring a safe and productive working environment for all</p>
        </div>
    </header>
    
    <div class="nav-container">
        <nav class="container">
            <ul>
                <li><a href="#safety">Safety Procedures</a></li>
                <li><a href="#access">Lab Access</a></li>
                <li><a href="#equipment">Equipment Use</a></li>
                <li><a href="#chemicals">Chemical Handling</a></li>
                <li><a href="#waste">Waste Disposal</a></li>
                <li><a href="#emergency">Emergency Procedures</a></li>
            </ul>
        </nav>
    </div>
    
    <main class="container">
        <section id="safety" class="policy-section">
            <h2>Safety Procedures</h2>
            
            <h3>Personal Protective Equipment (PPE)</h3>
            <ul>
                <li>Safety goggles must be worn at all times in the laboratory</li>
                <li>Lab coats are required for all experiments</li>
                <li>Closed-toe shoes must be worn in the lab</li>
                <li>Gloves appropriate for the chemicals being used must be worn</li>
                <li>Long hair must be tied back</li>
            </ul>
            
            <div class="warning">
                <strong>Warning:</strong> Failure to wear appropriate PPE may result in suspension of lab privileges.
            </div>
            
            <h3>General Safety Rules</h3>
            <ul>
                <li>No eating, drinking, or smoking in the laboratory</li>
                <li>Never work alone in the lab</li>
                <li>Keep work areas clean and organized</li>
                <li>Know the location and proper use of safety equipment</li>
                <li>Report all accidents, injuries, or spills immediately</li>
            </ul>
        </section>
        
        <section id="access" class="policy-section">
            <h2>Lab Access & Hours</h2>
            
            <h3>Access Requirements</h3>
            <ul>
                <li>All personnel must complete safety training before accessing the lab</li>
                <li>Visitors must be accompanied by authorized personnel at all times</li>
                <li>After-hours access requires special permission</li>
                <li>All users must sign in and out of the laboratory</li>
            </ul>
            
            <h3>Operating Hours</h3>
            <ul>
                <li>Regular hours: Monday-Friday, 8:00 AM - 6:00 PM</li>
                <li>After-hours access: By prior arrangement only</li>
                <li>Weekend access: Requires supervisor approval</li>
            </ul>
            
            <div class="important">
                <strong>Note:</strong> The lab is closed on official holidays. Special arrangements may be made for time-sensitive experiments.
            </div>
        </section>
        
        <section id="equipment" class="policy-section">
            <h2>Equipment Use & Reservations</h2>
            
            <h3>General Equipment Rules</h3>
            <ul>
                <li>Complete training before using any specialized equipment</li>
                <li>Report malfunctioning equipment immediately</li>
                <li>Clean equipment after use</li>
                <li>Do not remove equipment from the lab without permission</li>
                <li>Log equipment usage as required</li>
            </ul>
            
            <h3>Reservation System</h3>
            <ul>
                <li>High-demand equipment must be reserved in advance</li>
                <li>Maximum usage time may apply during peak hours</li>
                <li>Cancel reservations if you cannot use the time slot</li>
                <li>Users are responsible for equipment during their reserved time</li>
            </ul>
        </section>
        
        <section id="chemicals" class="policy-section">
            <h2>Chemical Handling & Storage</h2>
            
            <h3>Chemical Procurement</h3>
            <ul>
                <li>All chemicals must be approved before purchase</li>
                <li>Maintain an inventory of all chemicals in the lab</li>
                <li>Check existing supplies before ordering new chemicals</li>
            </ul>
            
            <h3>Storage Guidelines</h3>
            <ul>
                <li>Store chemicals according to compatibility groups</li>
                <li>Keep containers properly labeled with contents and hazards</li>
                <li>Store volatile or toxic chemicals in ventilated cabinets</li>
                <li>Return chemicals to proper storage after use</li>
            </ul>
            
            <div class="warning">
                <strong>Important:</strong> Never store food or beverages in chemical refrigerators.
            </div>
        </section>
        
        <section id="waste" class="policy-section">
            <h2>Waste Disposal Procedures</h2>
            
            <h3>Chemical Waste</h3>
            <ul>
                <li>Separate waste by compatibility and hazard class</li>
                <li>Use appropriate, labeled containers for each waste type</li>
                <li>Do not mix incompatible wastes</li>
                <li>Complete waste tags with all required information</li>
            </ul>
            
            <h3>Biological Waste</h3>
            <ul>
                <li>Autoclave all biological waste before disposal</li>
                <li>Use designated biohazard containers</li>
                <li>Follow specific protocols for different biological materials</li>
            </ul>
            
            <h3>Sharps Disposal</h3>
            <ul>
                <li>Place all sharps in puncture-resistant containers</li>
                <li>Do not overfill sharps containers</li>
                <li>Never recap needles before disposal</li>
            </ul>
        </section>
        
        <section id="emergency" class="policy-section">
            <h2>Emergency Procedures</h2>
            
            <h3>Emergency Contacts</h3>
            <ul>
                <li>Emergency Services: 911</li>
                <li>Lab Manager: [Name] - [Phone Number]</li>
                <li>Safety Officer: [Name] - [Phone Number]</li>
                <li>Building Security: [Phone Number]</li>
            </ul>
            
            <h3>Emergency Equipment Locations</h3>
            <ul>
                <li>Fire extinguishers: Near each exit</li>
                <li>Emergency showers/eyewash: [Specific locations]</li>
                <li>First aid kits: [Specific locations]</li>
                <li>Spill kits: [Specific locations]</li>
            </ul>
            
            <h3>Emergency Response</h3>
            <ol>
                <li>Assess the situation and ensure your safety</li>
                <li>Alert others in the area</li>
                <li>Contact emergency services if needed</li>
                <li>Use appropriate safety equipment</li>
                <li>Evacuate if necessary using designated exits</li>
                <li>Assemble at the designated meeting point</li>
            </ol>
            
            <div class="important">
                <strong>Remember:</strong> In case of fire, activate the nearest fire alarm and evacuate immediately. Do not attempt to fight large fires.
            </div>
        </section>
    </main>
    
    <footer>
        <div class="container">
            <p>&copy; 2025 Laboratory Management | Last Updated: [Unknowed]</p>
            <p>For questions about these policies, contact the Lab Manager</p>
            <p>RFID LAB MANAGEMENT SYSTEM</p>
        </div>
    </footer>
</body>
</html>