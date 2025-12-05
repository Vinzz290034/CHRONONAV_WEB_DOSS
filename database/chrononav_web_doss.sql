CREATE TABLE academic_calendar_events (
    event_id INT(11) NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    event_type ENUM('Holiday', 'Exam', 'Enrollment', 'Deadline', '...') NOT NULL,
    created_by_user_id INT(11) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
    PRIMARY KEY (event_id),
    INDEX (created_by_user_id)
);

CREATE TABLE schedule_events (
    -- Primary Key
    id INT(11) NOT NULL AUTO_INCREMENT,

    -- Event Identification
    schedule_code VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,

    -- Event Type and Timing
    schedule_type ENUM('class', 'meeting', 'event', 'holiday') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE DEFAULT NULL,
    start_time TIME NOT NULL,
    end_time TIME DEFAULT NULL,
    day_of_week VARCHAR(50) DEFAULT NULL,
    repeat_frequency ENUM('none', 'daily', 'weekly', 'monthly') NOT NULL,

    -- Contextual Data
    room VARCHAR(255) DEFAULT NULL,
    user_id INT(11) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,

    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),

    -- Define Primary Key
    PRIMARY KEY (id)
);

CREATE TABLE announcements (
    -- Unique identifier for the announcement (Primary Key, Auto-Incremented)
    id INT(11) NOT NULL AUTO_INCREMENT,

    -- ID of the user who published the announcement (Foreign Key)
    user_id INT(11) NOT NULL,

    -- Title of the announcement
    title VARCHAR(255) NOT NULL,

    -- The main body/text of the announcement
    content TEXT NOT NULL,

    -- Timestamp for when the record was published (Default: current time)
    published_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    -- Timestamp for the last update to the record (Default: current time, updates on modification)
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),

    -- Optional path to a related image file
    image_path VARCHAR(255) DEFAULT NULL,

    -- Define Primary Key
    PRIMARY KEY (id)
);

CREATE TABLE attendance_record (
    -- Unique identifier for the attendance record (Primary Key, Auto-Incremented)
    id INT(11) NOT NULL AUTO_INCREMENT,

    -- Foreign Key to the specific class/meeting session
    session_id INT(11) NOT NULL,

    -- Foreign Key to the student being recorded
    student_id INT(11) NOT NULL,

    -- The attendance status for the student in this session
    status ENUM('Present', 'Absent', 'Late', 'Excused', 'Tardy', '...') NOT NULL DEFAULT 'Absent',

    -- Optional time the student arrived
    time_in TIME DEFAULT NULL,

    -- Optional time the student left
    time_out TIME DEFAULT NULL,

    -- Any additional notes regarding the attendance
    notes TEXT DEFAULT NULL,

    -- Timestamp when this record was created/logged
    recorded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    -- Define Primary Key
    PRIMARY KEY (id),

    -- Define indices for efficient lookups/joins
    INDEX (session_id),
    INDEX (student_id)
);

CREATE TABLE audit_log (
    -- Unique identifier for the log entry (Primary Key, Auto-Incremented)
    id INT(11) NOT NULL AUTO_INCREMENT,

    -- ID of the user who performed the action (Foreign Key)
    user_id INT(11) NOT NULL,

    -- Description of the action performed (e.g., 'User Login', 'Updated Profile', 'Deleted File')
    action VARCHAR(255) NOT NULL,

    -- Timestamp of the action
    timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    -- Detailed information about the action (e.g., old/new values, resource ID)
    details TEXT DEFAULT NULL,

    -- IP address from which the action originated
    ip_address VARCHAR(45) DEFAULT NULL,

    -- Information about the client software (browser, operating system)
    user_agent TEXT DEFAULT NULL,

    -- Define Primary Key
    PRIMARY KEY (id),

    -- Define index for efficient lookups by user and time
    INDEX (user_id),
    INDEX (timestamp)
);

CREATE TABLE calendar_events (
    -- Unique identifier for the event (Primary Key, Auto-Incremented)
    id INT(11) NOT NULL AUTO_INCREMENT,

    -- ID of the user who created the event (Foreign Key)
    user_id INT(11) UNSIGNED NOT NULL,

    -- Name or title of the event
    event_name VARCHAR(255) NOT NULL,

    -- Detailed description of the event (Optional)
    description TEXT DEFAULT NULL,

    -- Start date and time of the event
    start_date DATETIME NOT NULL,

    -- End date and time of the event
    end_date DATETIME NOT NULL,

    -- Physical location of the event (Optional)
    location VARCHAR(255) DEFAULT NULL,

    -- Category or type of event
    event_type VARCHAR(50) NOT NULL,

    -- Timestamp for when the record was created
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    -- Timestamp for the last update to the record (Updates on modification)
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),

    -- Define Primary Key
    PRIMARY KEY (id),

    -- Define index for efficient lookups by user_id
    INDEX (user_id)
);

CREATE TABLE classes (
    -- Unique identifier for the class (Primary Key, Auto-Incremented)
    class_id INT(11) NOT NULL AUTO_INCREMENT,

    -- Full name of the class/course
    class_name VARCHAR(255) NOT NULL,

    -- Abbreviated code for the class (e.g., 'CS101', 'HIST205')
    class_code VARCHAR(50) NOT NULL,

    -- ID of the faculty member assigned to the class (Foreign Key)
    faculty_id INT(11) NOT NULL,

    -- ID of the physical room where the class is held (Optional Foreign Key)
    room_id INT(11) DEFAULT NULL,

    -- The semester the class is offered (e.g., 'Fall 2025', 'Spring 2026')
    semester VARCHAR(50) DEFAULT NULL,

    -- The academic year the class belongs to (e.g., '2025-2026')
    academic_year VARCHAR(9) DEFAULT NULL,

    -- Days of the week the class meets (e.g., 'MWF', 'TTh', 'Sat')
    day_of_week VARCHAR(10) DEFAULT NULL,

    -- Time the class starts
    start_time TIME DEFAULT NULL,

    -- Time the class ends
    end_time TIME DEFAULT NULL,

    -- Timestamp for when the record was created
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    -- Timestamp for the last update to the record (Updates on modification)
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),

    -- Define Primary Key
    PRIMARY KEY (class_id),

    -- Define indices for efficient lookups/joins
    INDEX (faculty_id),
    INDEX (class_code)
);

CREATE TABLE class_enrollments (
    -- Unique identifier for the enrollment record (Primary Key, Auto-Incremented)
    id INT(11) NOT NULL AUTO_INCREMENT,

    -- Foreign Key to the class being enrolled in
    class_id INT(11) NOT NULL,

    -- Foreign Key to the student who is enrolling
    student_id INT(11) NOT NULL,

    -- Date and time of the enrollment transaction (Optional)
    enrollment_date DATETIME DEFAULT CURRENT_TIMESTAMP(),

    -- Status of the enrollment (e.g., 'Enrolled', 'Withdrawn', 'Completed')
    status VARCHAR(50) DEFAULT 'Enrolled',

    -- Timestamp for when the record was created
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    -- Timestamp for the last update to the record (Updates on modification)
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),

    -- Define Primary Key
    PRIMARY KEY (id),

    -- Define unique composite key (to prevent a student from enrolling in the same class twice)
    UNIQUE KEY uk_class_student (class_id, student_id)
);

CREATE TABLE class_sessions (
    -- Unique identifier for the session record (Primary Key, Auto-Incremented)
    id INT(11) NOT NULL AUTO_INCREMENT,

    -- Foreign Key to the class this session belongs to
    class_id INT(11) NOT NULL,

    -- The date the session took place
    session_date DATE NOT NULL,

    -- The actual time the session started (Optional, for tracking deviations from the schedule)
    actual_start_time TIME DEFAULT NULL,

    -- The actual time the session ended (Optional)
    actual_end_time TIME DEFAULT NULL,

    -- Foreign Key to the physical room used for the session (Optional)
    room_id INT(11) DEFAULT NULL,

    -- ID of the user (e.g., faculty) who recorded the session's details/attendance
    recorded_by_user_id INT(11) NOT NULL,

    -- Notes about the session (e.g., topics covered, incidents)
    notes TEXT DEFAULT NULL,

    -- Timestamp for when the record was created
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    -- Timestamp for the last update to the record (Updates on modification)
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),

    -- Define Primary Key
    PRIMARY KEY (id),

    -- Index for efficient lookups by class and date
    INDEX uk_class_date (class_id, session_date)
);

CREATE TABLE class_students (
    -- Unique identifier for the enrollment record (Primary Key, Auto-Incremented)
    id INT(11) NOT NULL AUTO_INCREMENT,

    -- Foreign Key to the class
    class_id INT(11) NOT NULL,

    -- Foreign Key to the student
    student_id INT(11) NOT NULL,

    -- Timestamp for when the student was enrolled in the class
    enrollment_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    -- Define Primary Key
    PRIMARY KEY (id),

    -- Define unique composite key (to prevent duplicate enrollment records)
    UNIQUE KEY uk_class_student (class_id, student_id)
);

CREATE TABLE consultation_hours (
    -- Unique identifier for the schedule entry (Primary Key, Auto-Incremented)
    id INT(11) NOT NULL AUTO_INCREMENT,

    -- Foreign Key to the faculty member the hours belong to
    faculty_id INT(11) NOT NULL,

    -- Day of the week for the consultation (e.g., 'Monday', 'Tuesday')
    day_of_week VARCHAR(20) NOT NULL,

    -- Time the consultation starts
    start_time TIME NOT NULL,

    -- Time the consultation ends
    end_time TIME NOT NULL,

    -- Status flag to indicate if the hours are currently active (0 for inactive, 1 for active)
    is_active TINYINT(1) NOT NULL DEFAULT 1,

    -- Timestamp for when the record was created
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    -- Define Primary Key
    PRIMARY KEY (id),

    -- Index for efficient lookups by faculty
    INDEX (faculty_id)
);

CREATE TABLE events (
    -- Unique identifier for the event (Primary Key, Auto-Incremented)
    id INT(11) NOT NULL AUTO_INCREMENT,

    -- ID of the user who created the event (Foreign Key)
    user_id INT(11) NOT NULL,

    -- Short name or title of the event
    title VARCHAR(255) NOT NULL,

    -- Detailed description of the event (Optional)
    description TEXT DEFAULT NULL,

    -- Type of event occurrence (e.g., 'one_time', 'recurring')
    event_type ENUM('one_time', 'recurring') NOT NULL,

    -- Start date of the event (Optional for recurring events)
    start_date DATE DEFAULT NULL,

    -- End date of the event (Optional)
    end_date DATE DEFAULT NULL,

    -- Day of the week for recurring events (e.g., 'Mon', 'Wed', 'Fri')
    day_of_week VARCHAR(10) DEFAULT NULL,

    -- Time the event starts
    start_time TIME NOT NULL,

    -- Time the event ends (Optional)
    end_time TIME DEFAULT NULL,

    -- Physical location of the event (Optional)
    location VARCHAR(255) DEFAULT NULL,

    -- Flag to indicate if the event is completed (0=No, 1=Yes)
    is_completed TINYINT(1) DEFAULT 0,

    -- Timestamp for when the record was created
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    -- Define Primary Key
    PRIMARY KEY (id),

    -- Index for efficient lookups by user_id
    INDEX (user_id)
);

CREATE TABLE faqs (
    -- Unique identifier for the FAQ entry (Primary Key, Auto-Incremented)
    id INT(11) NOT NULL AUTO_INCREMENT,

    -- The text of the question
    question TEXT NOT NULL,

    -- The text of the answer
    answer TEXT NOT NULL,

    -- Timestamp for when the record was created
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    -- Timestamp for the last update to the record (Updates on modification)
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),

    -- Define Primary Key
    PRIMARY KEY (id)
);


CREATE TABLE feedback (
    -- Unique identifier for the feedback entry (Primary Key, Auto-Incremented)
    feedback_id INT(11) NOT NULL AUTO_INCREMENT,

    -- ID of the user who submitted the feedback (Optional, Foreign Key)
    user_id INT(11) DEFAULT NULL,

    -- Category of the feedback (e.g., 'Bug Report', 'Feature Request', 'General Fee...')
    feedback_type ENUM('Bug Report', 'Feature Request', 'General Feedback', '...') NOT NULL,

    -- Subject line or brief title of the feedback (Optional)
    subject VARCHAR(255) DEFAULT NULL,

    -- The full message/body of the feedback
    message TEXT NOT NULL,

    -- Optional rating provided by the user (e.g., 1-5)
    rating INT(11) DEFAULT NULL,

    -- Current processing status of the feedback
    status ENUM('New', 'In Progress', 'Resolved', 'Archived') DEFAULT 'New',

    -- Timestamp for when the feedback was submitted
    submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    -- Define Primary Key
    PRIMARY KEY (feedback_id),

    -- Index for efficient lookups by user
    INDEX (user_id)
);

CREATE TABLE login_attempts (
    -- Unique identifier for the log entry (Primary Key, Auto-Incremented)
    id INT(11) NOT NULL AUTO_INCREMENT,

    -- ID of the user (if known/successful)
    user_id INT(11) DEFAULT NULL,

    -- The username used for the attempt
    username VARCHAR(255) NOT NULL,

    -- IP address from which the attempt originated
    ip_address VARCHAR(45) NOT NULL,

    -- Timestamp of the login attempt
    attempt_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    -- Outcome of the attempt
    status ENUM('success', 'failed') NOT NULL,

    -- Information about the client software (browser, operating system)
    user_agent TEXT DEFAULT NULL,

    -- Define Primary Key
    PRIMARY KEY (id),

    -- Index for efficient lookups by username and time
    INDEX idx_username (username),
    INDEX idx_ip_address (ip_address)
);

CREATE TABLE notifications (
    -- Unique identifier for the notification (Primary Key, Auto-Incremented)
    id INT(11) NOT NULL AUTO_INCREMENT,

    -- ID of the user who is receiving the notification (Foreign Key)
    user_id INT(11) NOT NULL,

    -- The content of the notification message
    message TEXT NOT NULL,

    -- Optional link associated with the notification (e.g., to a specific page or resource)
    link VARCHAR(255) DEFAULT NULL,

    -- Read status of the notification (0 = Unread, 1 = Read)
    is_read TINYINT(1) NOT NULL DEFAULT 0,

    -- Timestamp for when the notification was created/sent
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    -- Define Primary Key
    PRIMARY KEY (id),

    -- Index for efficient lookups by user and read status
    INDEX idx_user_read (user_id, is_read)
);

CREATE TABLE notification_preferences (
    -- Foreign Key to the user the preferences belong to (Primary Key in many designs)
    user_id INT(11) NOT NULL,

    -- Flag for receiving email notifications (1=Yes, 0=No)
    email_notifications TINYINT(1) NOT NULL DEFAULT 1,

    -- Flag for receiving SMS notifications (1=Yes, 0=No)
    sms_notifications TINYINT(1) NOT NULL DEFAULT 0,

    -- Flag for receiving notifications within the application (1=Yes, 0=No)
    in_app_notifications TINYINT(1) NOT NULL DEFAULT 1,

    -- Number of days before an event to send a reminder
    reminder_days_before INT(11) NOT NULL DEFAULT 1,

    -- Timestamp for the last update to the record (Updates on modification)
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),

    -- Define Primary Key (Assuming one preference record per user)
    PRIMARY KEY (user_id)
);

CREATE TABLE office_hours (
    -- Unique identifier for the schedule entry (Primary Key, Auto-Incremented)
    oh_id INT(11) NOT NULL AUTO_INCREMENT,

    -- Foreign Key to the faculty member the hours belong to
    faculty_id INT(11) NOT NULL,

    -- Day of the week for the consultation (e.g., 'Mon', 'Tues', '...')
    day_of_week VARCHAR(10) NOT NULL,

    -- Time the consultation starts
    start_time TIME NOT NULL,

    -- Time the consultation ends
    end_time TIME NOT NULL,

    -- Physical location for the office hours (Optional)
    location VARCHAR(255) DEFAULT NULL,

    -- Timestamp for when the record was created
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    -- Timestamp for the last update to the record (Updates on modification)
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),

    -- Define Primary Key
    PRIMARY KEY (oh_id),

    -- Index for efficient lookups by faculty
    INDEX (faculty_id)
);

CREATE TABLE office_hours_request (
    -- Unique identifier for the request (Primary Key, Auto-Incremented)
    id INT(11) NOT NULL AUTO_INCREMENT,

    -- Foreign Key to the faculty member making the request
    faculty_id INT(11) NOT NULL,

    -- The proposed day of the week for the change
    proposed_day VARCHAR(20) NOT NULL,

    -- The proposed start time for the change
    proposed_start_time TIME NOT NULL,

    -- The proposed end time for the change
    proposed_end_time TIME NOT NULL,

    -- Faculty's justification or message for the request
    request_letter_message TEXT NOT NULL,

    -- Status of the request (pending, rejected, approved, revised)
    status ENUM('pending', 'rejected', 'approved', 'revised') NOT NULL DEFAULT 'pending',

    -- Administrative reply or reasoning for the decision (Optional)
    admin_reply_message TEXT DEFAULT NULL,

    -- Timestamp of when the request was submitted
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    -- Date and time of the administrative response (Optional)
    responded_at DATETIME DEFAULT NULL,

    -- The final approved day, if different from proposed (Optional)
    approved_day VARCHAR(20) DEFAULT NULL,

    -- The final approved start time (Optional)
    approved_start_time TIME DEFAULT NULL,

    -- The final approved end time (Optional)
    approved_end_time TIME DEFAULT NULL,

    -- Define Primary Key
    PRIMARY KEY (id),

    -- Index for efficient lookups by faculty
    INDEX (faculty_id)
);

CREATE TABLE onboarding_steps (
    -- Unique identifier for the step (Primary Key, Auto-Incremented)
    id INT(11) NOT NULL AUTO_INCREMENT,

    -- The user role the step applies to (e.g., 'user', 'admin', 'faculty')
    role VARCHAR(50) NOT NULL,

    -- The sequential order of the step within a role's guide
    step_order INT(11) NOT NULL,

    -- The title or headline for the guide step
    title VARCHAR(255) NOT NULL,

    -- The detailed content or body of the guide step
    content TEXT NOT NULL,

    -- Define Primary Key
    PRIMARY KEY (id),

    -- Define a unique constraint to ensure steps are ordered correctly per role
    UNIQUE KEY uk_role_step (role, step_order)
);

CREATE TABLE reminders (
    -- Unique identifier for the reminder (Primary Key, Auto-Incremented)
    reminder_id INT(11) NOT NULL AUTO_INCREMENT,

    -- ID of the user who owns the reminder (Foreign Key)
    user_id INT(11) NOT NULL,

    -- Short title for the reminder/task
    title VARCHAR(255) NOT NULL,

    -- Detailed description of the reminder/task (Optional)
    description TEXT DEFAULT NULL,

    -- The date the reminder is due
    due_date DATE NOT NULL,

    -- The specific time the reminder is due (Optional)
    due_time TIME DEFAULT NULL,

    -- Completion status of the reminder (0=Incomplete, 1=Completed)
    is_completed TINYINT(1) DEFAULT 0,

    -- Timestamp for when the record was created
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    -- Timestamp for the last update to the record (Updates on modification)
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),

    -- Define Primary Key
    PRIMARY KEY (reminder_id),

    -- Index for efficient lookups by user
    INDEX (user_id)
);

CREATE TABLE rooms (
    -- Unique identifier for the room (Primary Key, Auto-Incremented)
    id INT(11) NOT NULL AUTO_INCREMENT,

    -- Official name or code of the room (e.g., 'A101', 'Lecture Hall 2')
    room_name VARCHAR(100) NOT NULL,

    -- Maximum capacity of the room (Optional)
    capacity INT(11) DEFAULT NULL,

    -- Type of room for categorization
    room_type ENUM('Classroom', 'Laboratory', 'Lecture Hall', 'Other') DEFAULT 'Classroom',

    -- List of major equipment in the room (e.g., Projector, Whiteboard, Computers)
    equipment TEXT DEFAULT NULL,

    -- Detailed description of the room's location (e.g., 'Third floor, main building')
    location_description TEXT DEFAULT NULL,

    -- Timestamp for when the record was created
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    -- Timestamp for the last update to the record (Updates on modification)
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),

    -- Define Primary Key
    PRIMARY KEY (id),

    -- Ensure room names are unique
    UNIQUE KEY uk_room_name (room_name)
);

CREATE TABLE schedules (
    -- Unique identifier for the schedule entry (Primary Key, Auto-Incremented)
    schedule_id INT(11) NOT NULL AUTO_INCREMENT,

    -- ID of the user who owns or created the schedule (Optional Foreign Key)
    user_id INT(11) DEFAULT NULL,

    -- ID of the faculty member involved (Optional Foreign Key)
    faculty_id INT(11) DEFAULT NULL,

    -- ID of the room reserved for the schedule (Optional Foreign Key)
    room_id INT(11) DEFAULT NULL,

    -- Title or short name of the scheduled event
    title VARCHAR(255) NOT NULL,

    -- Detailed description of the schedule (Optional)
    description TEXT DEFAULT NULL,

    -- The day of the week the schedule applies to
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', '...') NOT NULL,

    -- The time the scheduled activity starts
    start_time TIME NOT NULL,

    -- The time the scheduled activity ends
    end_time TIME NOT NULL,

    -- The academic year this schedule applies to (Optional)
    academic_year VARCHAR(50) DEFAULT NULL,

    -- The semester this schedule applies to (Optional)
    semester ENUM('First Semester', 'Second Semester', 'Summer', '...') DEFAULT NULL,

    -- Timestamp for when the record was created
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    -- Timestamp for the last update to the record (Updates on modification)
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),

    -- Define Primary Key
    PRIMARY KEY (schedule_id),

    -- Index for efficient lookups by faculty and room
    INDEX (faculty_id),
    INDEX (room_id)
);

CREATE TABLE tickets (
    -- Unique identifier for the ticket (Primary Key, Auto-Incremented)
    id INT(11) NOT NULL AUTO_INCREMENT,

    -- ID of the user who submitted the ticket (Foreign Key)
    user_id INT(11) NOT NULL,

    -- Brief subject line or title of the issue
    subject VARCHAR(255) NOT NULL,

    -- The full message or detailed description of the issue
    message TEXT NOT NULL,

    -- Current status of the ticket
    status ENUM('open', 'in progress', 'closed') DEFAULT 'open',

    -- Administrative reply or resolution message (Optional)
    admin_reply TEXT DEFAULT NULL,

    -- Timestamp for when the ticket was created/submitted
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    -- Timestamp for the last update to the ticket (Updates on modification)
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),

    -- Define Primary Key
    PRIMARY KEY (id),

    -- Index for efficient lookups by user and status
    INDEX idx_user_status (user_id, status)
);

CREATE TABLE ticket_replies (
    -- Unique identifier for the reply (Primary Key, Auto-Incremented)
    id INT(11) NOT NULL AUTO_INCREMENT,

    -- Foreign Key to the ticket this reply belongs to
    ticket_id INT(11) NOT NULL,

    -- ID of the user (student/faculty) who sent the reply (Optional)
    user_id INT(11) DEFAULT NULL,

    -- ID of the admin/staff member who sent the reply (Optional)
    admin_id INT(11) DEFAULT NULL,

    -- The content of the reply message
    message TEXT NOT NULL,

    -- Timestamp for when the reply was created
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    -- Define Primary Key
    PRIMARY KEY (id),

    -- Index for efficient lookups by ticket
    INDEX (ticket_id)
);

CREATE TABLE users (
    -- Unique identifier for the user (Primary Key, Auto-Incremented)
    id INT(11) NOT NULL AUTO_INCREMENT,

    -- User's full name
    name VARCHAR(100) NOT NULL,

    -- User's login email address (must be unique)
    email VARCHAR(100) NOT NULL UNIQUE,

    -- User's password hash (field type 'password' is used for demonstration, but should be a hash like VARCHAR(255))
    password VARCHAR(255) NOT NULL,

    -- User's role in the system (e.g., 'user', 'faculty', 'admin')
    role ENUM('user', 'faculty', 'admin', '...') NOT NULL DEFAULT 'user',

    -- A course identifier, if applicable (e.g., student's major, faculty's primary course)
    course VARCHAR(100) DEFAULT NULL,

    -- The department the user belongs to
    department VARCHAR(100) DEFAULT NULL,

    -- Foreign key to a related faculty profile ID (for students or admins linking to faculty)
    faculty_id VARCHAR(50) DEFAULT NULL,

    -- Default path to the profile image file
    profile_img VARCHAR(255) NOT NULL DEFAULT 'uploads/profiles/default-avatar.png',

    -- Onboarding status flag (0=Incomplete, 1=Completed)
    is_onboarding_completed TINYINT(1) DEFAULT 0,

    -- Timestamp for when the account was created
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    -- Timestamp for the last update to the account record
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),

    -- Unique student ID (Optional for students)
    student_id VARCHAR(50) DEFAULT NULL,

    -- Unique admin ID (Optional for admins)
    admin_id VARCHAR(50) DEFAULT NULL,

    -- Account active status (0=Inactive, 1=Active)
    is_active TINYINT(1) DEFAULT 1,

    -- Current status description
    status VARCHAR(20) NOT NULL DEFAULT 'active',

    -- Define Primary Key
    PRIMARY KEY (id),

    -- Ensure email is indexed for fast login lookups
    INDEX (email)
);

CREATE TABLE user_calendar_events (
    -- Unique identifier for the event (Primary Key, Auto-Incremented)
    id INT(11) NOT NULL AUTO_INCREMENT,

    -- ID of the user the event belongs to (Foreign Key)
    user_id INT(11) NOT NULL,

    -- Foreign Key to a master calendar event, if applicable (Optional)
    calendar_event_id INT(11) DEFAULT NULL,

    -- Name or title of the event
    event_name VARCHAR(255) NOT NULL,

    -- Detailed description of the event (Optional)
    description TEXT DEFAULT NULL,

    -- Start date and time of the event
    start_date DATETIME NOT NULL,

    -- End date and time of the event
    end_date DATETIME NOT NULL,

    -- Physical location of the event (Optional)
    location VARCHAR(255) DEFAULT NULL,

    -- Category or type of event
    event_type VARCHAR(50) NOT NULL,

    -- Flag indicating if the event is a user-created personal event (1=Personal, 0=Master)
    is_personal TINYINT(1) DEFAULT 1,

    -- Timestamp for when the record was created
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    -- Timestamp for the last update to the record (Updates on modification)
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),

    -- Define Primary Key
    PRIMARY KEY (id),

    -- Index for efficient lookups by user
    INDEX (user_id)
);

CREATE TABLE user_schedule (
    -- Unique identifier for the schedule entry (Primary Key, Auto-Incremented)
    id INT(11) NOT NULL AUTO_INCREMENT,

    -- Foreign Key to the user the schedule belongs to
    user_id INT(11) NOT NULL,

    -- A scheduling code or identifier (Optional)
    sched_no VARCHAR(50) DEFAULT NULL,

    -- The course number/code (e.g., 'CS101', 'HIST205')
    course_no VARCHAR(50) NOT NULL,

    -- The time the scheduled activity starts
    time VARCHAR(50) NOT NULL,

    -- The days of the week the activity meets (e.g., 'MWF', 'TTH')
    days VARCHAR(50) NOT NULL,

    -- The room where the activity takes place
    room VARCHAR(50) NOT NULL,

    -- The number of academic units/credits for the activity
    units DECIMAL(3,1) DEFAULT 0.0,

    -- The name of the instructor (Optional)
    instructor VARCHAR(100) DEFAULT NULL,

    -- Timestamp for when the record was created
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    -- Define Primary Key
    PRIMARY KEY (id),

    -- Index for efficient lookups by user and course
    INDEX (user_id),
    INDEX (course_no)
);

CREATE TABLE ocr_templates (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(512) NOT NULL COMMENT 'Path or name of the template file (PDF/Image)',
    status ENUM('draft', 'active', 'error') DEFAULT 'draft' COMMENT 'Template processing status',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);