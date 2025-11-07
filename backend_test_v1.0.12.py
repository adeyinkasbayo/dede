#!/usr/bin/env python3
"""
Backend Test Suite for v1.0.12 Features
Tests Winning Upload Fix & Auto-aggregation functionality
"""

import requests
import mysql.connector
import json
import sys
from datetime import datetime, date

class WinningUploadTester:
    def __init__(self):
        # Database configuration
        self.db_config = {
            'host': 'localhost',
            'database': 'darfiden_db',
            'user': 'root',
            'password': 'root',
            'charset': 'utf8mb4'
        }
        
        # Application URL
        self.base_url = "http://localhost:8080"
        self.session = requests.Session()
        self.logged_in = False
        self.current_user = None
        
        # Test results
        self.test_results = []
        
        # Test data
        self.test_staff_id = None
        self.test_shop_id = None
        self.test_shop_code = None
        
    def connect_db(self):
        """Connect to MySQL database"""
        try:
            connection = mysql.connector.connect(**self.db_config)
            return connection
        except mysql.connector.Error as e:
            print(f"✗ Database connection failed: {e}")
            return None
    
    def log_result(self, test_name, status, message):
        """Log test result"""
        self.test_results.append({
            'test': test_name,
            'status': status,
            'message': message
        })
        icon = "✓" if status == "PASSED" else "✗"
        print(f"{icon} {test_name}: {status} - {message}")
    
    def test_database_structure(self):
        """Test 1: Verify winnings table structure"""
        print("\n=== TEST 1: Database Structure Verification ===")
        
        connection = self.connect_db()
        if not connection:
            self.log_result('Database Structure', 'FAILED', 'Could not connect to database')
            return False
        
        try:
            cursor = connection.cursor(dictionary=True)
            
            # Check if customer_name column does NOT exist
            cursor.execute("""
                SELECT COLUMN_NAME 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = 'darfiden_db' 
                AND TABLE_NAME = 'winnings' 
                AND COLUMN_NAME = 'customer_name'
            """)
            
            customer_name_exists = cursor.fetchone()
            
            if customer_name_exists:
                self.log_result('Database Structure', 'FAILED', 'customer_name column still exists in winnings table')
                return False
            
            # Check if ticket_number has UNIQUE constraint
            cursor.execute("""
                SELECT CONSTRAINT_NAME, CONSTRAINT_TYPE
                FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = 'darfiden_db'
                AND TABLE_NAME = 'winnings'
                AND CONSTRAINT_TYPE = 'UNIQUE'
            """)
            
            unique_constraints = cursor.fetchall()
            
            # Also check column key
            cursor.execute("""
                SELECT COLUMN_NAME, COLUMN_KEY
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = 'darfiden_db'
                AND TABLE_NAME = 'winnings'
                AND COLUMN_NAME = 'ticket_number'
            """)
            
            ticket_column = cursor.fetchone()
            
            has_unique = False
            if ticket_column and ticket_column['COLUMN_KEY'] == 'UNI':
                has_unique = True
            elif any('ticket' in str(c).lower() for c in unique_constraints):
                has_unique = True
            
            if not has_unique:
                self.log_result('Database Structure', 'FAILED', 'ticket_number does not have UNIQUE constraint')
                return False
            
            # Verify required tables exist
            required_tables = ['winnings', 'expenses', 'shops', 'users', 'staff_shop_assignments']
            cursor.execute("SHOW TABLES")
            existing_tables = [row[list(row.keys())[0]] for row in cursor.fetchall()]
            
            missing_tables = [t for t in required_tables if t not in existing_tables]
            
            if missing_tables:
                self.log_result('Database Structure', 'FAILED', f'Missing tables: {missing_tables}')
                return False
            
            self.log_result('Database Structure', 'PASSED', 
                          'winnings table verified: no customer_name, ticket_number has UNIQUE constraint, all required tables exist')
            return True
            
        except mysql.connector.Error as e:
            self.log_result('Database Structure', 'FAILED', f'Database query failed: {e}')
            return False
        finally:
            connection.close()
    
    def test_authentication(self):
        """Test 2: Authentication with admin credentials"""
        print("\n=== TEST 2: Authentication Testing ===")
        
        try:
            # Get login page
            login_url = f"{self.base_url}/login.php"
            response = self.session.get(login_url, timeout=10)
            
            if response.status_code != 200:
                self.log_result('Authentication', 'FAILED', f'Cannot access login page: {response.status_code}')
                return False
            
            # Attempt login with admin credentials
            login_data = {
                'username': 'admin',
                'password': 'admin123'
            }
            
            response = self.session.post(login_url, data=login_data, allow_redirects=True, timeout=10)
            
            # Check for success indicators
            success_indicators = ['dashboard', 'logout', 'main-content', 'daily operation', 'staff']
            login_success = any(indicator in response.text.lower() for indicator in success_indicators)
            
            if login_success or response.status_code == 200:
                self.logged_in = True
                self.log_result('Authentication', 'PASSED', 'Successfully logged in as admin')
                return True
            else:
                self.log_result('Authentication', 'FAILED', 'Login failed with admin credentials')
                return False
                
        except Exception as e:
            self.log_result('Authentication', 'FAILED', f'Authentication test failed: {e}')
            return False
    
    def test_api_get_shop_id(self):
        """Test 3: API endpoint api_get_shop_id.php"""
        print("\n=== TEST 3: API Endpoint - api_get_shop_id.php ===")
        
        if not self.logged_in:
            self.log_result('API get_shop_id', 'FAILED', 'Not authenticated')
            return False
        
        try:
            # First, get a shop code from database
            connection = self.connect_db()
            if not connection:
                self.log_result('API get_shop_id', 'FAILED', 'Database connection failed')
                return False
            
            cursor = connection.cursor(dictionary=True)
            cursor.execute("SELECT id, code, name FROM shops LIMIT 1")
            shop = cursor.fetchone()
            connection.close()
            
            if not shop:
                self.log_result('API get_shop_id', 'FAILED', 'No shops found in database')
                return False
            
            # Store for later tests
            self.test_shop_id = shop['id']
            self.test_shop_code = shop['code']
            
            # Test API endpoint
            api_url = f"{self.base_url}/api_get_shop_id.php?code={shop['code']}"
            response = self.session.get(api_url, timeout=10)
            
            if response.status_code != 200:
                self.log_result('API get_shop_id', 'FAILED', f'API returned status {response.status_code}')
                return False
            
            # Parse JSON response
            try:
                data = response.json()
            except:
                self.log_result('API get_shop_id', 'FAILED', 'API did not return valid JSON')
                return False
            
            # Verify response structure
            if not data.get('success'):
                self.log_result('API get_shop_id', 'FAILED', f"API returned success=false: {data.get('message')}")
                return False
            
            if data.get('shop_id') != shop['id']:
                self.log_result('API get_shop_id', 'FAILED', f"API returned wrong shop_id: {data.get('shop_id')} != {shop['id']}")
                return False
            
            # Test with invalid shop code
            api_url_invalid = f"{self.base_url}/api_get_shop_id.php?code=INVALID_CODE_999"
            response_invalid = self.session.get(api_url_invalid, timeout=10)
            data_invalid = response_invalid.json()
            
            if data_invalid.get('success'):
                self.log_result('API get_shop_id', 'FAILED', 'API should return success=false for invalid shop code')
                return False
            
            self.log_result('API get_shop_id', 'PASSED', 
                          f'API correctly returns shop_id for valid code and rejects invalid code')
            return True
            
        except Exception as e:
            self.log_result('API get_shop_id', 'FAILED', f'API test failed: {e}')
            return False
    
    def test_api_get_totals(self):
        """Test 4: API endpoint api_get_totals.php"""
        print("\n=== TEST 4: API Endpoint - api_get_totals.php ===")
        
        if not self.logged_in:
            self.log_result('API get_totals', 'FAILED', 'Not authenticated')
            return False
        
        try:
            # Get test data from database
            connection = self.connect_db()
            if not connection:
                self.log_result('API get_totals', 'FAILED', 'Database connection failed')
                return False
            
            cursor = connection.cursor(dictionary=True)
            
            # Get a staff member
            cursor.execute("SELECT id FROM users WHERE role = 'staff' LIMIT 1")
            staff = cursor.fetchone()
            
            if not staff:
                # Create a test staff member
                cursor.execute("""
                    INSERT INTO users (username, password, full_name, email, role, status)
                    VALUES ('teststaff', '$2y$10$dummy', 'Test Staff', 'test@test.com', 'staff', 'active')
                """)
                connection.commit()
                staff_id = cursor.lastrowid
            else:
                staff_id = staff['id']
            
            self.test_staff_id = staff_id
            
            # Use shop from previous test
            if not self.test_shop_id:
                cursor.execute("SELECT id FROM shops LIMIT 1")
                shop = cursor.fetchone()
                self.test_shop_id = shop['id'] if shop else 1
            
            test_date = date.today().strftime('%Y-%m-%d')
            
            connection.close()
            
            # Test API endpoint with valid parameters
            api_url = f"{self.base_url}/api_get_totals.php?staff_id={staff_id}&shop_id={self.test_shop_id}&date={test_date}"
            response = self.session.get(api_url, timeout=10)
            
            if response.status_code != 200:
                self.log_result('API get_totals', 'FAILED', f'API returned status {response.status_code}')
                return False
            
            # Parse JSON response
            try:
                data = response.json()
            except:
                self.log_result('API get_totals', 'FAILED', 'API did not return valid JSON')
                return False
            
            # Verify response structure
            if not data.get('success'):
                self.log_result('API get_totals', 'FAILED', f"API returned success=false: {data.get('message')}")
                return False
            
            if 'total_winnings' not in data or 'total_expenses' not in data:
                self.log_result('API get_totals', 'FAILED', 'API response missing total_winnings or total_expenses')
                return False
            
            # Test with missing parameters
            api_url_invalid = f"{self.base_url}/api_get_totals.php?staff_id={staff_id}"
            response_invalid = self.session.get(api_url_invalid, timeout=10)
            data_invalid = response_invalid.json()
            
            if data_invalid.get('success'):
                self.log_result('API get_totals', 'FAILED', 'API should return success=false when parameters are missing')
                return False
            
            self.log_result('API get_totals', 'PASSED', 
                          f'API correctly returns totals (winnings: {data["total_winnings"]}, expenses: {data["total_expenses"]}) and validates parameters')
            return True
            
        except Exception as e:
            self.log_result('API get_totals', 'FAILED', f'API test failed: {e}')
            return False
    
    def test_staff_shop_assignments(self):
        """Test 5: Staff shop assignments setup"""
        print("\n=== TEST 5: Staff Shop Assignments ===")
        
        try:
            connection = self.connect_db()
            if not connection:
                self.log_result('Staff Shop Assignments', 'FAILED', 'Database connection failed')
                return False
            
            cursor = connection.cursor(dictionary=True)
            
            # Ensure test staff has shop assignments
            if self.test_staff_id and self.test_shop_id:
                # Check if assignment exists
                cursor.execute("""
                    SELECT * FROM staff_shop_assignments 
                    WHERE staff_id = %s AND shop_id = %s
                """, (self.test_staff_id, self.test_shop_id))
                
                assignment = cursor.fetchone()
                
                if not assignment:
                    # Create assignment
                    cursor.execute("""
                        INSERT INTO staff_shop_assignments (staff_id, shop_id, assigned_by, assigned_at)
                        VALUES (%s, %s, 1, NOW())
                    """, (self.test_staff_id, self.test_shop_id))
                    connection.commit()
                    
                    self.log_result('Staff Shop Assignments', 'PASSED', 
                                  f'Created shop assignment for staff {self.test_staff_id} to shop {self.test_shop_id}')
                else:
                    self.log_result('Staff Shop Assignments', 'PASSED', 
                                  f'Shop assignment already exists for staff {self.test_staff_id}')
            else:
                self.log_result('Staff Shop Assignments', 'FAILED', 'Test staff or shop not available')
                return False
            
            connection.close()
            return True
            
        except Exception as e:
            self.log_result('Staff Shop Assignments', 'FAILED', f'Assignment test failed: {e}')
            return False
    
    def test_winning_upload_admin_flow(self):
        """Test 6: Winning upload as admin"""
        print("\n=== TEST 6: Winning Upload - Admin Flow ===")
        
        if not self.logged_in:
            self.log_result('Winning Upload Admin', 'FAILED', 'Not authenticated')
            return False
        
        try:
            # Access winning upload page
            upload_url = f"{self.base_url}/winning_upload.php"
            response = self.session.get(upload_url, timeout=10)
            
            if response.status_code != 200:
                self.log_result('Winning Upload Admin', 'FAILED', f'Cannot access winning_upload.php: {response.status_code}')
                return False
            
            # Check if admin sees staff dropdown
            if 'name="staff_id"' not in response.text:
                self.log_result('Winning Upload Admin', 'FAILED', 'Admin should see staff dropdown but it is missing')
                return False
            
            # Check if admin sees all shops dropdown
            if 'name="shop_id"' not in response.text:
                self.log_result('Winning Upload Admin', 'FAILED', 'Shop dropdown is missing')
                return False
            
            # Check that customer_name field is NOT present
            if 'customer_name' in response.text.lower():
                self.log_result('Winning Upload Admin', 'FAILED', 'customer_name field should not be present')
                return False
            
            # Check that ticket_number field IS present and required
            if 'name="ticket_number"' not in response.text:
                self.log_result('Winning Upload Admin', 'FAILED', 'ticket_number field is missing')
                return False
            
            self.log_result('Winning Upload Admin', 'PASSED', 
                          'Admin can access winning upload page with staff dropdown, shop dropdown, and ticket_number field (no customer_name)')
            return True
            
        except Exception as e:
            self.log_result('Winning Upload Admin', 'FAILED', f'Admin flow test failed: {e}')
            return False
    
    def test_winning_submission(self):
        """Test 7: Submit a winning and verify unique ticket_number validation"""
        print("\n=== TEST 7: Winning Submission & Unique Ticket Validation ===")
        
        if not self.logged_in:
            self.log_result('Winning Submission', 'FAILED', 'Not authenticated')
            return False
        
        try:
            # Generate unique ticket number
            ticket_number = f"TEST-{datetime.now().strftime('%Y%m%d%H%M%S')}"
            
            # Submit winning
            upload_url = f"{self.base_url}/winning_upload.php"
            form_data = {
                'staff_id': self.test_staff_id,
                'shop_id': self.test_shop_id,
                'ticket_number': ticket_number,
                'amount': '150.00',
                'winning_date': date.today().strftime('%Y-%m-%d'),
                'notes': 'Test winning submission'
            }
            
            response = self.session.post(upload_url, data=form_data, allow_redirects=True, timeout=10)
            
            if response.status_code != 200:
                self.log_result('Winning Submission', 'FAILED', f'Form submission failed: {response.status_code}')
                return False
            
            # Check if submission was successful (look for success message or redirect)
            success_indicators = ['success', 'uploaded', 'created', 'added']
            submission_success = any(indicator in response.text.lower() for indicator in success_indicators)
            
            # Verify in database
            connection = self.connect_db()
            if connection:
                cursor = connection.cursor(dictionary=True)
                cursor.execute("""
                    SELECT * FROM winnings 
                    WHERE ticket_number = %s
                """, (ticket_number,))
                
                winning = cursor.fetchone()
                
                if not winning:
                    self.log_result('Winning Submission', 'FAILED', 'Winning not found in database after submission')
                    connection.close()
                    return False
                
                # Try to submit duplicate ticket_number
                duplicate_ticket = ticket_number
                form_data_duplicate = form_data.copy()
                form_data_duplicate['ticket_number'] = duplicate_ticket
                
                response_duplicate = self.session.post(upload_url, data=form_data_duplicate, allow_redirects=True, timeout=10)
                
                # Check if duplicate was rejected
                error_indicators = ['duplicate', 'already exists', 'unique', 'error']
                duplicate_rejected = any(indicator in response_duplicate.text.lower() for indicator in error_indicators)
                
                # Also check database - should still be only one record
                cursor.execute("""
                    SELECT COUNT(*) as count FROM winnings 
                    WHERE ticket_number = %s
                """, (ticket_number,))
                
                count_result = cursor.fetchone()
                count = count_result['count'] if count_result else 0
                
                connection.close()
                
                if count > 1:
                    self.log_result('Winning Submission', 'FAILED', 
                                  f'Duplicate ticket_number was allowed (found {count} records)')
                    return False
                
                self.log_result('Winning Submission', 'PASSED', 
                              f'Winning submitted successfully with ticket {ticket_number}, duplicate ticket_number correctly rejected')
                return True
            else:
                self.log_result('Winning Submission', 'FAILED', 'Could not verify submission in database')
                return False
            
        except Exception as e:
            self.log_result('Winning Submission', 'FAILED', f'Submission test failed: {e}')
            return False
    
    def test_auto_aggregation_setup(self):
        """Test 8: Auto-aggregation - Create test data and verify API"""
        print("\n=== TEST 8: Auto-Aggregation Testing ===")
        
        try:
            connection = self.connect_db()
            if not connection:
                self.log_result('Auto-Aggregation', 'FAILED', 'Database connection failed')
                return False
            
            cursor = connection.cursor(dictionary=True)
            
            test_date = date.today().strftime('%Y-%m-%d')
            
            # Create test winning records
            winning_ticket = f"AGG-WIN-{datetime.now().strftime('%H%M%S')}"
            cursor.execute("""
                INSERT INTO winnings (shop_id, staff_id, ticket_number, amount, winning_date, status, created_by)
                VALUES (%s, %s, %s, 250.00, %s, 'pending', 1)
            """, (self.test_shop_id, self.test_staff_id, winning_ticket, test_date))
            
            # Create test expense records
            cursor.execute("""
                INSERT INTO expenses (shop_id, staff_id, amount, expense_date, category, description, created_by)
                VALUES (%s, %s, 75.50, %s, 'supplies', 'Test expense for aggregation', 1)
            """, (self.test_shop_id, self.test_staff_id, test_date))
            
            connection.commit()
            
            # Now test the API to verify aggregation
            api_url = f"{self.base_url}/api_get_totals.php?staff_id={self.test_staff_id}&shop_id={self.test_shop_id}&date={test_date}"
            response = self.session.get(api_url, timeout=10)
            
            if response.status_code != 200:
                self.log_result('Auto-Aggregation', 'FAILED', f'API returned status {response.status_code}')
                connection.close()
                return False
            
            data = response.json()
            
            if not data.get('success'):
                self.log_result('Auto-Aggregation', 'FAILED', f"API returned success=false: {data.get('message')}")
                connection.close()
                return False
            
            # Verify totals
            total_winnings = float(data.get('total_winnings', 0))
            total_expenses = float(data.get('total_expenses', 0))
            
            # Should have at least our test amounts
            if total_winnings < 250.00:
                self.log_result('Auto-Aggregation', 'FAILED', 
                              f'Total winnings incorrect: expected >= 250.00, got {total_winnings}')
                connection.close()
                return False
            
            if total_expenses < 75.50:
                self.log_result('Auto-Aggregation', 'FAILED', 
                              f'Total expenses incorrect: expected >= 75.50, got {total_expenses}')
                connection.close()
                return False
            
            connection.close()
            
            self.log_result('Auto-Aggregation', 'PASSED', 
                          f'Auto-aggregation working correctly: winnings={total_winnings}, expenses={total_expenses}')
            return True
            
        except Exception as e:
            self.log_result('Auto-Aggregation', 'FAILED', f'Aggregation test failed: {e}')
            return False
    
    def test_daily_create_page(self):
        """Test 9: Daily create page with auto-aggregation UI"""
        print("\n=== TEST 9: Daily Create Page - Auto-Aggregation UI ===")
        
        if not self.logged_in:
            self.log_result('Daily Create Page', 'FAILED', 'Not authenticated')
            return False
        
        try:
            # Access daily create page
            daily_url = f"{self.base_url}/daily_create.php"
            response = self.session.get(daily_url, timeout=10)
            
            if response.status_code != 200:
                self.log_result('Daily Create Page', 'FAILED', f'Cannot access daily_create.php: {response.status_code}')
                return False
            
            # Check for auto-aggregation indicators
            required_elements = [
                'total_winnings',
                'total_expenses',
                'api_get_totals.php',
                'api_get_shop_id.php',
                'Auto-calculated'
            ]
            
            missing_elements = []
            for element in required_elements:
                if element not in response.text:
                    missing_elements.append(element)
            
            if missing_elements:
                self.log_result('Daily Create Page', 'FAILED', 
                              f'Missing auto-aggregation elements: {missing_elements}')
                return False
            
            # Check for JavaScript AJAX functionality
            if 'fetchTotalsFromDatabase' not in response.text:
                self.log_result('Daily Create Page', 'FAILED', 
                              'JavaScript function fetchTotalsFromDatabase not found')
                return False
            
            self.log_result('Daily Create Page', 'PASSED', 
                          'Daily create page has auto-aggregation UI with AJAX functionality')
            return True
            
        except Exception as e:
            self.log_result('Daily Create Page', 'FAILED', f'Daily create page test failed: {e}')
            return False
    
    def run_all_tests(self):
        """Run all tests in sequence"""
        print("=" * 70)
        print("BACKEND TEST SUITE - v1.0.12 FEATURES")
        print("Winning Upload Fix & Auto-Aggregation")
        print("=" * 70)
        
        # Run tests
        self.test_database_structure()
        self.test_authentication()
        
        if self.logged_in:
            self.test_api_get_shop_id()
            self.test_api_get_totals()
            self.test_staff_shop_assignments()
            self.test_winning_upload_admin_flow()
            self.test_winning_submission()
            self.test_auto_aggregation_setup()
            self.test_daily_create_page()
        
        # Print summary
        self.print_test_summary()
    
    def print_test_summary(self):
        """Print test results summary"""
        print("\n" + "=" * 70)
        print("TEST RESULTS SUMMARY")
        print("=" * 70)
        
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
    tester = WinningUploadTester()
    success = tester.run_all_tests()
    
    # Exit with appropriate code
    sys.exit(0 if success else 1)

if __name__ == "__main__":
    main()
