<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Specifications - Zenith RFID</title>
    <link href='https://cdn.jsdelivr.net/npm/boxicons@2.0.5/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .specs-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            color: white;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .device-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .device-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .device-card:hover .device-image img {
            transform: scale(1.05);
        }

        .device-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(45deg, #f0f0f0, #e0e0e0);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .device-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .device-content {
            padding: 25px;
        }


        .device-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 15px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .device-description {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .specs-list {
            list-style: none;
            margin-bottom: 20px;
        }

        .specs-list li {
            padding: 8px 0;
            border-bottom: 1px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .specs-list li:last-child {
            border-bottom: none;
        }

        .spec-label {
            font-weight: 600;
            color: #2c3e50;
        }

        .spec-value {
            color: #7f8c8d;
            text-align: right;
        }

        .device-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .spec-link {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9rem;
            transition: background 0.3s ease;
        }

        .spec-link:hover {
            background: #2980b9;
        }

        .spec-link i {
            margin-right: 5px;
        }

        .spec-link.documentation {
            background: #27ae60;
        }

        .spec-link.documentation:hover {
            background: #219a52;
        }

        .spec-link.purchase {
            background: #e74c3c;
        }

        .spec-link.purchase:hover {
            background: #c0392b;
        }

        /* Device-specific colors */
        .device-card.power-supply .device-image { background: linear-gradient(45deg, #ff6b6b, #ee5a24); }
        .device-card.raspberry-pi .device-image { background: linear-gradient(45deg, #a29bfe, #6c5ce7); }
        .device-card.oscilloscope .device-image { background: linear-gradient(45deg, #fd79a8, #e84393); }
        .device-card.voltmeter .device-image { background: linear-gradient(45deg, #fdcb6e, #f39c12); }
        .device-card.multimeter .device-image { background: linear-gradient(45deg, #00cec9, #00b894); }

        /* Responsive Design */
        @media (max-width: 768px) {
            .device-grid {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .device-content {
                padding: 20px;
            }
        }

        /* Loading animation for images */
        .device-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            padding: 12px 24px;
            background: white;
            color: #3498db;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .back-button:hover {
            background: #3498db;
            color: white;
            transform: translateX(-5px);
        }

        .back-button i {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="specs-container">
        <a href="../index.php" class="back-button">
            <i class='bx bx-arrow-back'></i>
            Back to Main Page
        </a>

        <div class="header">
            <h1>Device Specifications</h1>
            <p>Comprehensive technical specifications for laboratory equipment</p>
        </div>

        <div class="device-grid">
            <!-- Power Supply -->
            <div class="device-card power-supply">
                <div class="device-image">
                    <img src="../device_image/powersupply.jpg" alt="Raspberry Pi 4 Model B" onload="imageLoaded(this)">
                </div>
                <div class="device-content">
                    <h2 class="device-title">Power Supply Unit</h2>
                    <p class="device-description">High-performance laboratory power supply with precise voltage and current regulation.</p>
                    
                    <ul class="specs-list">
                        <li><span class="spec-label">Output Voltage</span><span class="spec-value">0-60V DC</span></li>
                        <li><span class="spec-label">Output Current</span><span class="spec-value">0-5A</span></li>
                        <li><span class="spec-label">Power Rating</span><span class="spec-value">300W</span></li>
                        <li><span class="spec-label">Voltage Resolution</span><span class="spec-value">1mV</span></li>
                        <li><span class="spec-label">Current Resolution</span><span class="spec-value">1mA</span></li>
                        <li><span class="spec-label">Ripple & Noise</span><span class="spec-value">< 1mV RMS</span></li>
                        <li><span class="spec-label">Interface</span><span class="spec-value">LCD Display, Rotary Encoder</span></li>
                        <li><span class="spec-label">Protection</span><span class="spec-value">OVP, OCP, OTP</span></li>
                    </ul>
                    
                    <div class="device-links">
                        <a href="https://www.ibm.com/docs/en/storage-networking?topic=director-power-supply-specifications" class="spec-link documentation" target="_blank">
                            <i class='bx bx-book'></i> Documentation
                        </a>
                    </div>
                </div>
            </div>

            <!-- Raspberry Pi -->
            <div class="device-card raspberry-pi">
                <div class="device-image">
                    <img src="../device_image/raspberrypi.jpg" alt="Raspberry Pi 4 Model B" onload="imageLoaded(this)">
                </div>
                <div class="device-content">
                    <h2 class="device-title">Raspberry Pi 4 Model B</h2>
                    <p class="device-description">Single-board computer with enhanced performance for various computing projects.</p>
                    
                    <ul class="specs-list">
                        <li><span class="spec-label">Processor</span><span class="spec-value">Broadcom BCM2711</span></li>
                        <li><span class="spec-label">CPU Cores</span><span class="spec-value">4× ARM Cortex-A72</span></li>
                        <!-- <li><span class="spec-label">Clock Speed</span><span class="spec-value">1.5GHz</span></li> -->
                        <li><span class="spec-label">RAM</span><span class="spec-value">2GB/4GB/8GB LPDDR4</span></li>
                        <li><span class="spec-label">Networking</span><span class="spec-value">Ethernet, 2.4/5.0 GHz Wi-Fi</span></li>
                        <li><span class="spec-label">Bluetooth</span><span class="spec-value">Bluetooth 5.0</span></li>
                        <li><span class="spec-label">USB Ports</span><span class="spec-value">2× USB 3.0, 2× USB 2.0</span></li>
                        <li><span class="spec-label">Video Output</span><span class="spec-value">2× micro-HDMI</span></li>
                        <li><span class="spec-label">Power Input</span><span class="spec-value">5V/3A via USB-C</span></li>
                    </ul>
                    
                    <div class="device-links">
                        <a href="https://www.raspberrypi.com/products/raspberry-pi-4-model-b/specifications/" class="spec-link documentation" target="_blank">
                            <i class='bx bx-book'></i> Specifications
                        </a>
                    </div>
                </div>
            </div>

            <!-- Oscilloscope -->
            <div class="device-card oscilloscope">
                <div class="device-image">
                    <img src="../device_image/olliscope.png" alt="Raspberry Pi 4 Model B" onload="imageLoaded(this)">
                </div>
                <div class="device-content">
                    <h2 class="device-title">High-Definition Oscilloscope</h2>
                    <p class="device-description">Professional oscilloscope with advanced signal analysis capabilities.</p>
                    
                    <ul class="specs-list">
                        <li><span class="spec-label">Bandwidth</span><span class="spec-value">4 GHz</span></li>
                        <li><span class="spec-label">Sample Rate</span><span class="spec-value">20 GSa/s</span></li>
                        <!-- <li><span class="spec-label">Analog Channels</span><span class="spec-value">4</span></li> -->
                        <li><span class="spec-label">Digital Channels</span><span class="spec-value">16 (MSO)</span></li>
                        <li><span class="spec-label">Memory Depth</span><span class="spec-value">400 Mpts</span></li>
                        <li><span class="spec-label">Display</span><span class="spec-value">15.6" Touchscreen</span></li>
                        <li><span class="spec-label">Waveform Update Rate</span><span class="spec-value">> 1,000,000 wfm/s</span></li>
                        <li><span class="spec-label">Vertical Resolution</span><span class="spec-value">12-bit ADC</span></li>
                        <li><span class="spec-label">Interface</span><span class="spec-value">LAN, USB, GPIB</span></li>
                    </ul>
                    
                    <div class="device-links">
                        <a href="https://www.keysight.com/us/en/product/MSOS404A/high-definition-oscilloscope-4-ghz-4-analog-16-digital-channels.html" class="spec-link documentation" target="_blank">
                            <i class='bx bx-book'></i> Product Page
                        </a>
                    </div>
                </div>
            </div>

            <!-- Voltmeter -->
            <div class="device-card voltmeter">
                <div class="device-image">
                    <img src="../device_image/voltmeter.jpg" alt="Raspberry Pi 4 Model B" onload="imageLoaded(this)">
                </div>
                <div class="device-content">
                    <h2 class="device-title">Digital Voltmeter</h2>
                    <p class="device-description">Precision digital voltmeter for accurate voltage measurements.</p>
                    
                    <ul class="specs-list">
                        <li><span class="spec-label">Voltage Range</span><span class="spec-value">0-1000V DC/AC</span></li>
                        <li><span class="spec-label">Basic Accuracy</span><span class="spec-value">±0.5%</span></li>
                        <li><span class="spec-label">Resolution</span><span class="spec-value">0.1V</span></li>
                        <li><span class="spec-label">Input Impedance</span><span class="spec-value">10 MΩ</span></li>
                        <li><span class="spec-label">Display</span><span class="spec-value">3.5 digit LCD</span></li>
                        <li><span class="spec-label">Sampling Rate</span><span class="spec-value">3 readings/sec</span></li>
                        <li><span class="spec-label">Power Supply</span><span class="spec-value">9V Battery</span></li>
                        <li><span class="spec-label">Safety Rating</span><span class="spec-value">CAT III 600V</span></li>
                    </ul>
                    
                    <div class="device-links">
                        <a href="https://uk.rs-online.com/web/p/voltmeters/1797576" class="spec-link purchase" target="_blank">
                            <i class='bx bx-cart'></i> Purchase Info
                        </a>
                    </div>
                </div>
            </div>

            <!-- RISC-V -->
                        <div class="device-card voltmeter">
                <div class="device-image">
                    <img src="../device_image/RISC-V.png" alt="Raspberry Pi 4 Model B" onload="imageLoaded(this)">
                </div>
                <div class="device-content">
                    <h2 class="device-title">RISC-V</h2>
                    <p class="device-description">Precision digital voltmeter for accurate voltage measurements.</p>
                    
                    <ul class="specs-list">
                        <li><span class="spec-label">CPU</span><span class="spec-value">ESP32-C6 32-bit RISC-V</span></li>
                        <li><span class="spec-label">Clock rate</span><span class="spec-value">160 MHz</span></li>
                        <li><span class="spec-label">Networking</span><span class="spec-value">2.4 GHz Wi-Fi6 Bluetooth 5</span></li>
                        <li><span class="spec-label">ROM</span><span class="spec-value">320KB ROM</span></li>
                        <li><span class="spec-label">RAM</span><span class="spec-value">512KB SRAM</span></li>
                        <li><span class="spec-label">Connection</span><span class="spec-value">SPI, I2C, I2S, PWM</span></li>
                        <li><span class="spec-label">RGB LED</span><span class="spec-value">GP8 Pin</span></li>
                        <li><span class="spec-label">Security</span><span class="spec-value">AES-128/256, hashing</span></li>
                    </ul>
                    
                    <div class="device-links">
                        <a href="https://uk.rs-online.com/web/p/voltmeters/1797576" class="spec-link purchase" target="_blank">
                            <i class='bx bx-cart'></i> Purchase Info
                        </a>
                    </div>
                </div>
            </div>

            <!-- Multimeter -->
            <div class="device-card multimeter">
                <div class="device-image">
                    <img src="../device_image/multimeter.jpeg" alt="Raspberry Pi 4 Model B" onload="imageLoaded(this)">
                </div>
                <div class="device-content">
                    <h2 class="device-title">Digital Multimeter (DMM)</h2>
                    <p class="device-description">Versatile digital multimeter for comprehensive electrical measurements.</p>
                    
                    <ul class="specs-list">
                        <li><span class="spec-label">DC Voltage</span><span class="spec-value">0-1000V ±0.1%</span></li>
                        <li><span class="spec-label">AC Voltage</span><span class="spec-value">0-750V ±0.5%</span></li>
                        <li><span class="spec-label">DC Current</span><span class="spec-value">0-10A ±0.2%</span></li>
                        <li><span class="spec-label">AC Current</span><span class="spec-value">0-10A ±0.5%</span></li>
                        <li><span class="spec-label">Resistance</span><span class="spec-value">0-50MΩ ±0.2%</span></li>
                        <li><span class="spec-label">Capacitance</span><span class="spec-value">0-100mF ±1%</span></li>
                        <li><span class="spec-label">Frequency</span><span class="spec-value">0-1MHz ±0.01%</span></li>
                        <!-- <li><span class="spec-label">Display</span><span class="spec-value">4.5 digit, 50000 counts</span></li> -->
                        <li><span class="spec-label">Interface</span><span class="spec-value">USB, Bluetooth</span></li>
                    </ul>
                    
                    <div class="device-links">
                        <a href="https://www.ni.com/docs/en-US/bundle/ni-elvis-iii-using-instruments/page/dmmspecs.html" class="spec-link documentation" target="_blank">
                            <i class='bx bx-book'></i> Technical Specs
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add loading animation removal when images are loaded
        document.addEventListener('DOMContentLoaded', function() {
            const deviceImages = document.querySelectorAll('.device-image');
            
            deviceImages.forEach(image => {
                // Simulate image loading
                setTimeout(() => {
                    image.style.background = 'linear-gradient(45deg, #f8f9fa, #e9ecef)';
                    image.querySelector('i').style.color = '#495057';
                }, 1000);
            });

            // Add smooth scroll behavior
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.querySelector(this.getAttribute('href')).scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });
        });
    </script>
</body>
</html>