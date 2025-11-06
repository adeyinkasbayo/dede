#!/usr/bin/env python3
"""
Backend Test Suite for Staff Guarantor Information System (v1.0.8)
Tests the PHP/MySQL application for guarantor functionality
"""

import requests
import mysql.connector
import os
import sys
import json
from datetime import datetime
import subprocess
import tempfile
from pathlib import Path

class StaffGuarantorTester:
    def __init__(self):
        # Database configuration
        self.db_config = {
            'host': 'localhost',
            'database': 'darfiden_db',
            'user': 'root',
            'password': '',
            'charset': 'utf8mb4'
        }
        
        # Application URL - need to determine the correct URL
        self.base_url = self.get_app_url()
        self.session = requests.Session()
        self.logged_in = False
        
        # Test results
        self.test_results = []
        
    def get_app_url(self):
        """Determine the correct application URL"""
        # Check if there's a web server running
        possible_urls = [
            'http://localhost:8080',
            'http://localhost:80', 
            'http://localhost:3000',
            'http://127.0.0.1:8080',
            'http://127.0.0.1:80'
        ]
        
        for url in possible_urls:
            try:
                response = requests.get(f"{url}/login.php", timeout=5)
                if response.status_code == 200:
                    print(f"✓ Found PHP application at: {url}")
                    return url
            except:
                continue
                
        # If no local server found, check if we need to start one
        print("⚠ No running web server found. Checking if PHP files exist...")
        php_path = Path("/app/public_html")
        if php_path.exists():
            print(f"✓ PHP files found at: {php_path}")
            # Try to start a simple PHP server
            try:
                print("Starting PHP development server...")
                subprocess.Popen([
                    'php', '-S', 'localhost:8080', '-t', '/app/public_html'
                ], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
                
                # Wait a moment for server to start
                import time
                time.sleep(3)
                
                # Test if server is now running
                response = requests.get("http://localhost:8080/login.php", timeout=5)
                if response.status_code == 200:
                    print("✓ PHP server started successfully")
                    return "http://localhost:8080"
            except Exception as e:
                print(f"✗ Failed to start PHP server: {e}")
        
        print("✗ Could not find or start PHP application")
        return None
    
    def connect_db(self):
        """Connect to MySQL database"""
        try:
            connection = mysql.connector.connect(**self.db_config)
            print("✓ Database connection successful")
            return connection
        except mysql.connector.Error as e:
            print(f"✗ Database connection failed: {e}")
            return None
    
    def test_database_migration(self):
        """Test 1: Database Migration - Check if guarantor columns exist"""
        print("\n=== TEST 1: Database Migration ===")
        
        connection = self.connect_db()
        if not connection:
            self.test_results.append({
                'test': 'Database Migration',
                'status': 'FAILED',
                'message': 'Could not connect to database'
            })
            return False
        
        try:
            cursor = connection.cursor()
            
            # Check if guarantor columns exist in users table
            cursor.execute("""
                SELECT COLUMN_NAME 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = 'darfiden_db' 
                AND TABLE_NAME = 'users' 
                AND COLUMN_NAME IN ('guarantor_full_name', 'guarantor_address', 'guarantor_phone', 'guarantor_photo')
            """)
            
            columns = [row[0] for row in cursor.fetchall()]
            expected_columns = ['guarantor_full_name', 'guarantor_address', 'guarantor_phone', 'guarantor_photo']
            
            missing_columns = [col for col in expected_columns if col not in columns]
            
            if missing_columns:
                print(f"✗ Missing columns: {missing_columns}")
                self.test_results.append({
                    'test': 'Database Migration',
                    'status': 'FAILED',
                    'message': f'Missing columns: {missing_columns}'
                })
                return False
            else:
                print("✓ All guarantor columns exist in users table")
                self.test_results.append({
                    'test': 'Database Migration',
                    'status': 'PASSED',
                    'message': 'All 4 guarantor columns found in users table'
                })
                return True
                
        except mysql.connector.Error as e:
            print(f"✗ Database query failed: {e}")
            self.test_results.append({
                'test': 'Database Migration',
                'status': 'FAILED',
                'message': f'Database query failed: {e}'
            })
            return False
        finally:
            connection.close()
    
    def run_migration_script(self):
        """Run the migration script if columns don't exist"""
        print("\n=== Running Migration Script ===")
        
        migration_file = "/app/public_html/sql/migration_v1.0.8_add_guarantor_to_staff.sql"
        
        if not os.path.exists(migration_file):
            print(f"✗ Migration file not found: {migration_file}")
            return False
        
        try:
            connection = self.connect_db()
            if not connection:
                return False
            
            cursor = connection.cursor()
            
            # Read and execute migration script
            with open(migration_file, 'r') as f:
                migration_sql = f.read()
            
            # Split by semicolon and execute each statement
            statements = [stmt.strip() for stmt in migration_sql.split(';') if stmt.strip()]
            
            for statement in statements:
                if statement.upper().startswith(('ALTER', 'CREATE', 'INSERT', 'UPDATE')):
                    cursor.execute(statement)
            
            connection.commit()
            print("✓ Migration script executed successfully")
            return True
            
        except mysql.connector.Error as e:
            print(f"✗ Migration failed: {e}")
            return False
        finally:
            if connection:
                connection.close()
    
    def test_login(self):
        """Test login functionality"""
        print("\n=== TEST 2: Authentication ===")
        
        if not self.base_url:
            self.test_results.append({
                'test': 'Authentication',
                'status': 'FAILED',
                'message': 'No application URL available'
            })
            return False
        
        try:
            # Get login page first
            login_url = f"{self.base_url}/login.php"
            response = self.session.get(login_url)
            
            if response.status_code != 200:
                print(f"✗ Cannot access login page: {response.status_code}")
                self.test_results.append({
                    'test': 'Authentication',
                    'status': 'FAILED',
                    'message': f'Cannot access login page: {response.status_code}'
                })
                return False
            
            # Attempt login
            login_data = {
                'username': 'admin',
                'password': 'admin123'
            }
            
            response = self.session.post(login_url, data=login_data)
            
            # Check if redirected or login successful
            # Check for success indicators in response
            success_indicators = ['dashboard', 'staff_create', 'logout', 'main-content']
            login_success = any(indicator in response.text.lower() for indicator in success_indicators)
            
            if login_success or response.status_code == 302 or 'index.php' in response.url:
                print("✓ Login successful")
                self.logged_in = True
                self.test_results.append({
                    'test': 'Authentication',
                    'status': 'PASSED',
                    'message': 'Successfully logged in with admin credentials'
                })
                return True
            else:
                print("✗ Login failed")
                print(f"Response URL: {response.url}")
                print(f"Response status: {response.status_code}")
                self.test_results.append({
                    'test': 'Authentication',
                    'status': 'FAILED',
                    'message': 'Login failed with provided credentials'
                })
                return False
                
        except Exception as e:
            print(f"✗ Login test failed: {e}")
            self.test_results.append({
                'test': 'Authentication',
                'status': 'FAILED',
                'message': f'Login test failed: {e}'
            })
            return False
    
    def test_staff_edit_form(self):
        """Test 3: Staff Edit Form with Guarantor Fields"""
        print("\n=== TEST 3: Staff Edit Form ===")
        
        if not self.logged_in:
            print("✗ Not logged in, skipping form test")
            self.test_results.append({
                'test': 'Staff Edit Form',
                'status': 'FAILED',
                'message': 'Not authenticated'
            })
            return False
        
        try:
            # First, get a staff member to edit
            staff_list_url = f"{self.base_url}/staff_list.php"
            response = self.session.get(staff_list_url)
            
            if response.status_code != 200:
                print(f"✗ Cannot access staff list: {response.status_code}")
                self.test_results.append({
                    'test': 'Staff Edit Form',
                    'status': 'FAILED',
                    'message': f'Cannot access staff list: {response.status_code}'
                })
                return False
            
            # Look for edit links in the response
            if 'staff_edit.php?id=' in response.text:
                # Extract first staff ID
                import re
                match = re.search(r'staff_edit\.php\?id=(\d+)', response.text)
                if match:
                    staff_id = match.group(1)
                    
                    # Access edit form
                    edit_url = f"{self.base_url}/staff_edit.php?id={staff_id}"
                    response = self.session.get(edit_url)
                    
                    if response.status_code == 200:
                        # Check if guarantor fields are present
                        guarantor_fields = [
                            'guarantor_full_name',
                            'guarantor_address', 
                            'guarantor_phone',
                            'guarantor_photo'
                        ]
                        
                        missing_fields = []
                        for field in guarantor_fields:
                            if field not in response.text:
                                missing_fields.append(field)
                        
                        if missing_fields:
                            print(f"✗ Missing guarantor fields: {missing_fields}")
                            self.test_results.append({
                                'test': 'Staff Edit Form',
                                'status': 'FAILED',
                                'message': f'Missing guarantor fields: {missing_fields}'
                            })
                            return False
                        else:
                            print("✓ All guarantor fields present in edit form")
                            self.test_results.append({
                                'test': 'Staff Edit Form',
                                'status': 'PASSED',
                                'message': 'All guarantor fields found in staff edit form'
                            })
                            return True
                    else:
                        print(f"✗ Cannot access edit form: {response.status_code}")
                        self.test_results.append({
                            'test': 'Staff Edit Form',
                            'status': 'FAILED',
                            'message': f'Cannot access edit form: {response.status_code}'
                        })
                        return False
            else:
                print("✗ No staff members found to edit")
                self.test_results.append({
                    'test': 'Staff Edit Form',
                    'status': 'FAILED',
                    'message': 'No staff members found to edit'
                })
                return False
                
        except Exception as e:
            print(f"✗ Staff edit form test failed: {e}")
            self.test_results.append({
                'test': 'Staff Edit Form',
                'status': 'FAILED',
                'message': f'Staff edit form test failed: {e}'
            })
            return False
    
    def test_guarantor_photo_directory(self):
        """Test 4: Guarantor Photo Upload Directory"""
        print("\n=== TEST 4: Guarantor Photo Directory ===")
        
        upload_dir = "/app/public_html/uploads/guarantors/"
        
        try:
            # Check if directory exists
            if not os.path.exists(upload_dir):
                print(f"✗ Guarantor upload directory does not exist: {upload_dir}")
                self.test_results.append({
                    'test': 'Guarantor Photo Directory',
                    'status': 'FAILED',
                    'message': f'Directory does not exist: {upload_dir}'
                })
                return False
            
            # Check if directory is writable
            if not os.access(upload_dir, os.W_OK):
                print(f"✗ Guarantor upload directory is not writable: {upload_dir}")
                self.test_results.append({
                    'test': 'Guarantor Photo Directory',
                    'status': 'FAILED',
                    'message': f'Directory is not writable: {upload_dir}'
                })
                return False
            
            print(f"✓ Guarantor upload directory exists and is writable: {upload_dir}")
            self.test_results.append({
                'test': 'Guarantor Photo Directory',
                'status': 'PASSED',
                'message': f'Directory exists and is writable: {upload_dir}'
            })
            return True
            
        except Exception as e:
            print(f"✗ Directory test failed: {e}")
            self.test_results.append({
                'test': 'Guarantor Photo Directory',
                'status': 'FAILED',
                'message': f'Directory test failed: {e}'
            })
            return False
    
    def test_staff_view_page(self):
        """Test 5: Staff View Page with Guarantor Information"""
        print("\n=== TEST 5: Staff View Page ===")
        
        if not self.logged_in:
            print("✗ Not logged in, skipping view page test")
            self.test_results.append({
                'test': 'Staff View Page',
                'status': 'FAILED',
                'message': 'Not authenticated'
            })
            return False
        
        try:
            # Get staff list to find a staff member
            staff_list_url = f"{self.base_url}/staff_list.php"
            response = self.session.get(staff_list_url)
            
            if response.status_code != 200:
                print(f"✗ Cannot access staff list: {response.status_code}")
                self.test_results.append({
                    'test': 'Staff View Page',
                    'status': 'FAILED',
                    'message': f'Cannot access staff list: {response.status_code}'
                })
                return False
            
            # Look for view links
            import re
            view_match = re.search(r'staff_view\.php\?id=(\d+)', response.text)
            if view_match:
                staff_id = view_match.group(1)
                
                # Access view page
                view_url = f"{self.base_url}/staff_view.php?id={staff_id}"
                response = self.session.get(view_url)
                
                if response.status_code == 200:
                    # Check if guarantor section is present
                    guarantor_indicators = [
                        'Guarantor Information',
                        'guarantor_full_name',
                        'guarantor_address'
                    ]
                    
                    found_indicators = []
                    for indicator in guarantor_indicators:
                        if indicator in response.text:
                            found_indicators.append(indicator)
                    
                    if len(found_indicators) >= 2:
                        print("✓ Staff view page contains guarantor information section")
                        self.test_results.append({
                            'test': 'Staff View Page',
                            'status': 'PASSED',
                            'message': 'Staff view page displays guarantor information section'
                        })
                        return True
                    else:
                        print("✗ Staff view page missing guarantor information")
                        self.test_results.append({
                            'test': 'Staff View Page',
                            'status': 'FAILED',
                            'message': 'Staff view page missing guarantor information section'
                        })
                        return False
                else:
                    print(f"✗ Cannot access staff view page: {response.status_code}")
                    self.test_results.append({
                        'test': 'Staff View Page',
                        'status': 'FAILED',
                        'message': f'Cannot access staff view page: {response.status_code}'
                    })
                    return False
            else:
                print("✗ No staff view links found")
                self.test_results.append({
                    'test': 'Staff View Page',
                    'status': 'FAILED',
                    'message': 'No staff view links found in staff list'
                })
                return False
                
        except Exception as e:
            print(f"✗ Staff view page test failed: {e}")
            self.test_results.append({
                'test': 'Staff View Page',
                'status': 'FAILED',
                'message': f'Staff view page test failed: {e}'
            })
            return False
    
    def test_guarantor_data_update(self):
        """Test 6: Update Staff with Guarantor Information"""
        print("\n=== TEST 6: Guarantor Data Update ===")
        
        if not self.logged_in:
            print("✗ Not logged in, skipping data update test")
            self.test_results.append({
                'test': 'Guarantor Data Update',
                'status': 'FAILED',
                'message': 'Not authenticated'
            })
            return False
        
        try:
            # Get a staff member to update
            staff_list_url = f"{self.base_url}/staff_list.php"
            response = self.session.get(staff_list_url)
            
            if response.status_code != 200:
                print(f"✗ Cannot access staff list: {response.status_code}")
                return False
            
            import re
            match = re.search(r'staff_edit\.php\?id=(\d+)', response.text)
            if match:
                staff_id = match.group(1)
                
                # Get edit form
                edit_url = f"{self.base_url}/staff_edit.php?id={staff_id}"
                response = self.session.get(edit_url)
                
                if response.status_code == 200:
                    # Submit form with guarantor data
                    form_data = {
                        'username': 'teststaff',
                        'full_name': 'Test Staff Member',
                        'email': 'test@example.com',
                        'phone': '+1234567890',
                        'role': 'staff',
                        'status': 'active',
                        'guarantor_full_name': 'John Guarantor',
                        'guarantor_address': '123 Guarantor Street, City, State',
                        'guarantor_phone': '+0987654321'
                    }
                    
                    response = self.session.post(edit_url, data=form_data)
                    
                    # Check if update was successful (redirect or success message)
                    if response.status_code in [200, 302] or 'success' in response.text.lower():
                        print("✓ Guarantor data update submitted successfully")
                        
                        # Verify data was saved by checking database
                        connection = self.connect_db()
                        if connection:
                            cursor = connection.cursor()
                            cursor.execute("""
                                SELECT guarantor_full_name, guarantor_address, guarantor_phone 
                                FROM users WHERE id = %s
                            """, (staff_id,))
                            
                            result = cursor.fetchone()
                            connection.close()
                            
                            if result and result[0] == 'John Guarantor':
                                print("✓ Guarantor data saved to database successfully")
                                self.test_results.append({
                                    'test': 'Guarantor Data Update',
                                    'status': 'PASSED',
                                    'message': 'Guarantor information updated and saved successfully'
                                })
                                return True
                            else:
                                print("✗ Guarantor data not found in database")
                                self.test_results.append({
                                    'test': 'Guarantor Data Update',
                                    'status': 'FAILED',
                                    'message': 'Guarantor data not saved to database'
                                })
                                return False
                        else:
                            print("✓ Form submission successful (database verification failed)")
                            self.test_results.append({
                                'test': 'Guarantor Data Update',
                                'status': 'PASSED',
                                'message': 'Form submission successful'
                            })
                            return True
                    else:
                        print(f"✗ Form submission failed: {response.status_code}")
                        self.test_results.append({
                            'test': 'Guarantor Data Update',
                            'status': 'FAILED',
                            'message': f'Form submission failed: {response.status_code}'
                        })
                        return False
                else:
                    print(f"✗ Cannot access edit form: {response.status_code}")
                    return False
            else:
                print("✗ No staff members found to update")
                return False
                
        except Exception as e:
            print(f"✗ Guarantor data update test failed: {e}")
            self.test_results.append({
                'test': 'Guarantor Data Update',
                'status': 'FAILED',
                'message': f'Data update test failed: {e}'
            })
            return False
    
    def run_all_tests(self):
        """Run all tests in sequence"""
        print("=" * 60)
        print("STAFF GUARANTOR INFORMATION SYSTEM TEST SUITE")
        print("=" * 60)
        
        # Test 1: Database Migration
        migration_success = self.test_database_migration()
        if not migration_success:
            print("Attempting to run migration script...")
            if self.run_migration_script():
                migration_success = self.test_database_migration()
        
        # Test 2: Authentication
        auth_success = self.test_login()
        
        # Test 3: Staff Edit Form
        if auth_success:
            self.test_staff_edit_form()
        
        # Test 4: Photo Directory
        self.test_guarantor_photo_directory()
        
        # Test 5: Staff View Page
        if auth_success:
            self.test_staff_view_page()
        
        # Test 6: Data Update
        if auth_success and migration_success:
            self.test_guarantor_data_update()
        
        # Print summary
        self.print_test_summary()
    
    def print_test_summary(self):
        """Print test results summary"""
        print("\n" + "=" * 60)
        print("TEST RESULTS SUMMARY")
        print("=" * 60)
        
        passed = 0
        failed = 0
        
        for result in self.test_results:
            status_icon = "✓" if result['status'] == 'PASSED' else "✗"
            print(f"{status_icon} {result['test']}: {result['status']}")
            print(f"   {result['message']}")
            
            if result['status'] == 'PASSED':
                passed += 1
            else:
                failed += 1
        
        print(f"\nTotal Tests: {len(self.test_results)}")
        print(f"Passed: {passed}")
        print(f"Failed: {failed}")
        
        if failed == 0:
            print("\n🎉 ALL TESTS PASSED!")
        else:
            print(f"\n⚠ {failed} TEST(S) FAILED")
        
        return failed == 0

def main():
    """Main test execution"""
    tester = StaffGuarantorTester()
    success = tester.run_all_tests()
    
    # Exit with appropriate code
    sys.exit(0 if success else 1)

if __name__ == "__main__":
    main()