<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KNAA Member Registration</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 600px;
            margin: 20px auto;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .section-title {
            color: #667eea;
            font-size: 18px;
            font-weight: bold;
            margin: 30px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        label {
            display: block;
            color: #333;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .required {
            color: #e74c3c;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .membership-toggle {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .membership-toggle label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .membership-toggle input[type="radio"] {
            margin-right: 8px;
            width: auto;
        }
        
        #student-fields {
            display: none;
        }
        
        #full-fields {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>KNAA Member Registration</h1>
        <p class="subtitle">Kenyan Nurses Association of America</p>
        
        <form action="register_handler.php" method="POST">
            
            <!-- Membership Type Selection -->
            <div class="section-title">Membership Type</div>
            <div class="membership-toggle">
                <label>
                    <input type="radio" name="membership_category" value="Full" checked onchange="toggleMembershipFields()">
                    Full Membership
                </label>
                <label>
                    <input type="radio" name="membership_category" value="Student" onchange="toggleMembershipFields()">
                    Student Membership
                </label>
            </div>
            
            <!-- Personal Information -->
            <div class="section-title">Personal Information</div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name <span class="required">*</span></label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name <span class="required">*</span></label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address <span class="required">*</span></label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number <span class="required">*</span></label>
                <input type="tel" id="phone" name="phone" placeholder="(555) 123-4567" required>
            </div>
            
            <!-- Address Information -->
            <div class="section-title">Address</div>
            
            <div class="form-group">
                <label for="street_address">Street Address <span class="required">*</span></label>
                <input type="text" id="street_address" name="street_address" placeholder="123 Main Street" required>
            </div>
            
            <div class="form-group">
                <label for="address_line2">Apartment, Suite, etc.</label>
                <input type="text" id="address_line2" name="address_line2" placeholder="Apt 4B">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="city">City <span class="required">*</span></label>
                    <input type="text" id="city" name="city" required>
                </div>
                
                <div class="form-group">
                    <label for="state">State <span class="required">*</span></label>
                    <select id="state" name="state" required>
                        <option value="">Select State</option>
                        <option value="AL">Alabama</option>
                        <option value="AK">Alaska</option>
                        <option value="AZ">Arizona</option>
                        <option value="AR">Arkansas</option>
                        <option value="CA">California</option>
                        <option value="CO">Colorado</option>
                        <option value="CT">Connecticut</option>
                        <option value="DE">Delaware</option>
                        <option value="FL">Florida</option>
                        <option value="GA">Georgia</option>
                        <option value="HI">Hawaii</option>
                        <option value="ID">Idaho</option>
                        <option value="IL">Illinois</option>
                        <option value="IN">Indiana</option>
                        <option value="IA">Iowa</option>
                        <option value="KS">Kansas</option>
                        <option value="KY">Kentucky</option>
                        <option value="LA">Louisiana</option>
                        <option value="ME">Maine</option>
                        <option value="MD">Maryland</option>
                        <option value="MA">Massachusetts</option>
                        <option value="MI">Michigan</option>
                        <option value="MN">Minnesota</option>
                        <option value="MS">Mississippi</option>
                        <option value="MO">Missouri</option>
                        <option value="MT">Montana</option>
                        <option value="NE">Nebraska</option>
                        <option value="NV">Nevada</option>
                        <option value="NH">New Hampshire</option>
                        <option value="NJ">New Jersey</option>
                        <option value="NM">New Mexico</option>
                        <option value="NY">New York</option>
                        <option value="NC">North Carolina</option>
                        <option value="ND">North Dakota</option>
                        <option value="OH">Ohio</option>
                        <option value="OK">Oklahoma</option>
                        <option value="OR">Oregon</option>
                        <option value="PA">Pennsylvania</option>
                        <option value="RI">Rhode Island</option>
                        <option value="SC">South Carolina</option>
                        <option value="SD">South Dakota</option>
                        <option value="TN">Tennessee</option>
                        <option value="TX">Texas</option>
                        <option value="UT">Utah</option>
                        <option value="VT">Vermont</option>
                        <option value="VA">Virginia</option>
                        <option value="WA">Washington</option>
                        <option value="WV">West Virginia</option>
                        <option value="WI">Wisconsin</option>
                        <option value="WY">Wyoming</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="zip_code">ZIP Code <span class="required">*</span></label>
                <input type="text" id="zip_code" name="zip_code" placeholder="12345" maxlength="10" required>
            </div>
            
            <!-- Education -->
            <div class="section-title">Education</div>
            
            <div class="form-group">
                <label for="education_level">Highest Level of Education <span class="required">*</span></label>
                <select id="education_level" name="education_level" required>
                    <option value="">Select Education Level</option>
                    <option value="High School Diploma">High School Diploma</option>
                    <option value="Associate Degree">Associate Degree</option>
                    <option value="Bachelor's Degree">Bachelor's Degree (BSN)</option>
                    <option value="Master's Degree">Master's Degree (MSN)</option>
                    <option value="Doctoral Degree">Doctoral Degree (DNP/PhD)</option>
                </select>
            </div>
            
            <!-- Full Membership Fields -->
            <div id="full-fields">
                <div class="section-title">Professional Information</div>
                
                <div class="form-group">
                    <label for="license_type">License Type <span class="required">*</span></label>
                    <select id="license_type" name="license_type">
                        <option value="">Select License Type</option>
                        <option value="LPN">LPN - Licensed Practical Nurse</option>
                        <option value="LVN">LVN - Licensed Vocational Nurse</option>
                        <option value="RN">RN - Registered Nurse</option>
                        <option value="NP">NP - Nurse Practitioner</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="licensure_state">Licensure State <span class="required">*</span></label>
                    <select id="licensure_state" name="licensure_state">
                        <option value="">Select State</option>
                        <option value="AL">Alabama</option>
                        <option value="AK">Alaska</option>
                        <option value="AZ">Arizona</option>
                        <option value="AR">Arkansas</option>
                        <option value="CA">California</option>
                        <option value="CO">Colorado</option>
                        <option value="CT">Connecticut</option>
                        <option value="DE">Delaware</option>
                        <option value="FL">Florida</option>
                        <option value="GA">Georgia</option>
                        <option value="HI">Hawaii</option>
                        <option value="ID">Idaho</option>
                        <option value="IL">Illinois</option>
                        <option value="IN">Indiana</option>
                        <option value="IA">Iowa</option>
                        <option value="KS">Kansas</option>
                        <option value="KY">Kentucky</option>
                        <option value="LA">Louisiana</option>
                        <option value="ME">Maine</option>
                        <option value="MD">Maryland</option>
                        <option value="MA">Massachusetts</option>
                        <option value="MI">Michigan</option>
                        <option value="MN">Minnesota</option>
                        <option value="MS">Mississippi</option>
                        <option value="MO">Missouri</option>
                        <option value="MT">Montana</option>
                        <option value="NE">Nebraska</option>
                        <option value="NV">Nevada</option>
                        <option value="NH">New Hampshire</option>
                        <option value="NJ">New Jersey</option>
                        <option value="NM">New Mexico</option>
                        <option value="NY">New York</option>
                        <option value="NC">North Carolina</option>
                        <option value="ND">North Dakota</option>
                        <option value="OH">Ohio</option>
                        <option value="OK">Oklahoma</option>
                        <option value="OR">Oregon</option>
                        <option value="PA">Pennsylvania</option>
                        <option value="RI">Rhode Island</option>
                        <option value="SC">South Carolina</option>
                        <option value="SD">South Dakota</option>
                        <option value="TN">Tennessee</option>
                        <option value="TX">Texas</option>
                        <option value="UT">Utah</option>
                        <option value="VT">Vermont</option>
                        <option value="VA">Virginia</option>
                        <option value="WA">Washington</option>
                        <option value="WV">West Virginia</option>
                        <option value="WI">Wisconsin</option>
                        <option value="WY">Wyoming</option>
                    </select>
                </div>
            </div>
            
            <!-- Student Membership Fields -->
            <div id="student-fields">
                <div class="section-title">Student Information</div>
                
                <div class="form-group">
                    <label for="current_school">Current College/University <span class="required">*</span></label>
                    <input type="text" id="current_school" name="current_school" placeholder="University Name">
                </div>
                
                <div class="form-group">
                    <label for="anticipated_completion">Anticipated Completion Date <span class="required">*</span></label>
                    <input type="date" id="anticipated_completion" name="anticipated_completion">
                </div>
            </div>
            
            <button type="submit" class="btn">Complete Registration</button>
        </form>
    </div>
    
    <script>
        function toggleMembershipFields() {
            const membershipType = document.querySelector('input[name="membership_category"]:checked').value;
            const fullFields = document.getElementById('full-fields');
            const studentFields = document.getElementById('student-fields');
            const licenseType = document.getElementById('license_type');
            const licensureState = document.getElementById('licensure_state');
            const currentSchool = document.getElementById('current_school');
            const anticipatedCompletion = document.getElementById('anticipated_completion');
            
            if (membershipType === 'Full') {
                fullFields.style.display = 'block';
                studentFields.style.display = 'none';
                licenseType.required = true;
                licensureState.required = true;
                currentSchool.required = false;
                anticipatedCompletion.required = false;
            } else {
                fullFields.style.display = 'none';
                studentFields.style.display = 'block';
                licenseType.required = false;
                licensureState.required = false;
                currentSchool.required = true;
                anticipatedCompletion.required = true;
            }
        }
    </script>
</body>
</html>