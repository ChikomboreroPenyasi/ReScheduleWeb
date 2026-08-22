<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule - Automated Timetabling System</title>
    <link rel="stylesheet" href="css/style.css">
    
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
</head>
<body>

    <header class="navbar">
        <div class="logo">Reschedule<span>.</span></div>
        <nav class="nav-links">
            <a href="#features">Features</a>
            <a href="#platforms">Platforms</a>
            <a href="#admin-management">Schedule Portal</a>
            <a href="login.php" class="btn-secondary">Log In</a>
            <a href="signup.php" class="btn-primary">Get Started</a>
        </nav>
    </header>

    <section class="hero">
        <div class="hero-container">
            <span class="badge">Next-Gen Timetabling Platform</span>
            <h1>Smarter Class & Exam Scheduling for Higher Ed</h1>
            <p>Reschedule replaces manual timetable assembly with an automated engine that resolves room conflicts, lecturer double-booking, and course overlaps across web and mobile.</p>
            <div class="hero-actions">
                <a href="signup.php" class="btn-primary-lg">Create Account</a>
                <a href="splash.html" class="btn-secondary-lg">Preview Mobile App</a>
            </div>
        </div>
    </section>

    <section id="features" class="features-section">
        <div class="section-header">
            <h2>Built to Eliminate Scheduling Friction</h2>
            <p>Engineered for administrative staff, lecturers, and students.</p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3>Conflict-Free Engine</h3>
                <p>Automated validation prevents room double-booking, venue capacity overflows, and lecturer time collisions instantly.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🔄</div>
                <h3>Real-Time Sync</h3>
                <p>Schedule updates, venue shifts, and time modifications push dynamically to both web accounts and mobile devices.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3>Android Companion</h3>
                <p>Students and faculty access their personalized daily timetable, receive shift notifications, and view offline schedules.</p>
            </div>
        </div>
    </section>

    <section id="platforms" class="platforms-section">
        <div class="section-header">
            <h2>Unified Across Platforms</h2>
            <p>Access your timetable anywhere, whether on a desktop browser or a mobile device.</p>
        </div>

        <div class="platforms-grid">
            <div class="platform-card">
                <span class="platform-tag">Web Portal</span>
                <h3>Administrative Control Center</h3>
                <ul>
                    <li>Full master schedule generation & room allocation</li>
                    <li>Lecturer availability management</li>
                    <li>PDF and CSV timetable exports</li>
                    <li>User role management (Admin, Staff, Student)</li>
                </ul>
            </div>

            <div class="platform-card accent">
                <span class="platform-tag">Android App</span>
                <h3>On-the-Go Schedule Access</h3>
                <ul>
                    <li>Instant daily and weekly class views</li>
                    <li>Push notifications for emergency venue changes</li>
                    <li>Offline timetable caching for fast viewing</li>
                    <li>Quick account sign-up and authentication</li>
                </ul>
            </div>
        </div>
    </section>

    <section id="admin-management" class="features-section" style="background-color: #f8f9fa; padding: 40px 20px;">
        <div class="section-header">
            <h2>Live Web Schedule Management</h2>
            <p>Add or update schedules below to broadcast changes instantly to the Android app.</p>
        </div>

        <div style="max-width: 800px; margin: 0 auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <form id="scheduleForm" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Course Code:</label>
                    <input type="text" id="courseCode" placeholder="e.g. ICT123" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                </div>

                <div>
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Course Name:</label>
                    <input type="text" id="courseName" placeholder="e.g. Web Development" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                </div>

                <div>
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Day of Week:</label>
                    <select id="dayOfWeek" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                    </select>
                </div>

                <div>
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Type:</label>
                    <select id="scheduleType" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                        <option value="Class">Class</option>
                        <option value="CA">CA</option>
                        <option value="Exam">Exam</option>
                    </select>
                </div>

                <div>
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Start Time:</label>
                    <input type="time" id="startTime" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                </div>

                <div>
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">End Time:</label>
                    <input type="time" id="endTime" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                </div>

                <div style="grid-column: span 2;">
                    <button type="submit" class="btn-primary-lg" style="width:100%; border:none; cursor:pointer;">Publish / Reschedule</button>
                </div>
            </form>

            <h3 style="margin-top: 30px;">Current Live Schedules</h3>
            <div style="overflow-x:auto;">
                <table id="scheduleTable" style="width:100%; border-collapse: collapse; margin-top:10px;">
                    <thead>
                        <tr style="background:#f2f2f2; text-align:left;">
                            <th style="padding:10px; border:1px solid #ddd;">Course Code</th>
                            <th style="padding:10px; border:1px solid #ddd;">Course Name</th>
                            <th style="padding:10px; border:1px solid #ddd;">Day</th>
                            <th style="padding:10px; border:1px solid #ddd;">Start Time</th>
                            <th style="padding:10px; border:1px solid #ddd;">End Time</th>
                            <th style="padding:10px; border:1px solid #ddd;">Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="cta-banner">
        <h2>Ready to simplify your institution's schedule?</h2>
        <p>Get started with Reschedule today across web and mobile.</p>
        <a href="signup.php" class="btn-primary-lg">Sign Up Now</a>
    </section>

    <footer>
        <div class="footer-container">
            <p>&copy; <?php echo date('Y'); ?> Reschedule Timetabling System. All rights reserved.</p>
        </div>
    </footer>

    <script src="js/main.js"></script>

    <script>
        // Configured Supabase Credentials
        const SUPABASE_URL = "https://qthipmlvcmmveoijndsn.supabase.co";
        const SUPABASE_ANON_KEY = "sb_publishable_Hf0qHeWqyNfn3Mla1zBSHA_OYgNRbO2";

        const supabaseClient = supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

        // Fetch and display schedules from Supabase
        async function loadSchedules() {
            const { data, error } = await supabaseClient
                .from('schedules')
                .select('*');

            if (error) {
                console.error('Error fetching schedules:', error);
                return;
            }

            const tbody = document.querySelector('#scheduleTable tbody');
            if (tbody) {
                tbody.innerHTML = '';
                data.forEach(item => {
                    const row = `<tr>
                        <td style="padding:8px; border:1px solid #ddd;">${item.course_code || ''}</td>
                        <td style="padding:8px; border:1px solid #ddd;">${item.course_name || ''}</td>
                        <td style="padding:8px; border:1px solid #ddd;">${item.day_of_week}</td>
                        <td style="padding:8px; border:1px solid #ddd;">${item.start_time}</td>
                        <td style="padding:8px; border:1px solid #ddd;">${item.end_time}</td>
                        <td style="padding:8px; border:1px solid #ddd;">${item.type}</td>
                    </tr>`;
                    tbody.innerHTML += row;
                });
            }
        }

        // Handle Schedule Form Submission
        const form = document.getElementById('scheduleForm');
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                const courseCode = document.getElementById('courseCode').value;
                const courseName = document.getElementById('courseName').value;
                const dayOfWeek = document.getElementById('dayOfWeek').value;
                const startTime = document.getElementById('startTime').value;
                const endTime = document.getElementById('endTime').value;
                const type = document.getElementById('scheduleType').value;

                const { data, error } = await supabaseClient
                    .from('schedules')
                    .insert([
                        {
                            course_id: 1, 
                            room_id: 1,   
                            course_code: courseCode,
                            course_name: courseName,
                            day_of_week: dayOfWeek,
                            start_time: startTime,
                            end_time: endTime,
                            type: type
                        }
                    ]);

                if (error) {
                    alert('Failed to publish schedule: ' + error.message);
                } else {
                    alert('Schedule published successfully! Mobile app will update automatically.');
                    form.reset();
                    loadSchedules();
                }
            });
        }

        // Load schedule list when web page opens
        loadSchedules();
    </script>
</body>
</html>