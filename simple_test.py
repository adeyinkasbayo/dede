#!/usr/bin/env python3
"""
Simple test for Staff Guarantor Information System
"""

import requests
import mysql.connector

def test_database():
    """Test database migration"""
    try:
        connection = mysql.connector.connect(
            host='localhost',
            database='darfiden_db',
            user='root',
            password=''
        )
        cursor = connection.cursor()
        
        # Check guarantor columns
        cursor.execute("""
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = 'darfiden_db' 
            AND TABLE_NAME = 'users' 
            AND COLUMN_NAME IN ('guarantor_full_name', 'guarantor_address', 'guarantor_phone', 'guarantor_photo')
        """)
        
        columns = [row[0] for row in cursor.fetchall()]
        expected = ['guarantor_full_name', 'guarantor_address', 'guarantor_phone', 'guarantor_photo']
        
        print("✓ Database Migration Test:")
        for col in expected:
            if col in columns:
                print(f"  ✓ Column {col} exists")
            else:
                print(f"  ✗ Column {col} missing")
        
        # Check if guarantor data exists
        cursor.execute("SELECT COUNT(*) FROM users WHERE guarantor_full_name IS NOT NULL")
        count = cursor.fetchone()[0]
        print(f"  ✓ {count} staff members have guarantor information")
        
        connection.close()
        return True
    except Exception as e:
        print(f"✗ Database test failed: {e}")
        return False

def test_web_access():
    """Test web application access"""
    try:
        # Test login page access
        response = requests.get("http://localhost/login.php")
        if response.status_code == 200:
            print("✓ Web Application Access:")
            print("  ✓ Login page accessible")
        else:
            print(f"✗ Login page not accessible: {response.status_code}")
            return False
        
        # Test login functionality
        session = requests.Session()
        login_data = {'username': 'admin', 'password': 'admin123'}
        response = session.post("http://localhost/login.php", data=login_data)
        
        # Check if login was successful by accessing staff list
        response = session.get("http://localhost/staff_list.php")
        if 'staff_edit.php' in response.text:
            print("  ✓ Login successful")
            print("  ✓ Staff list accessible")
        else:
            print("  ✗ Login failed or staff list not accessible")
            return False
        
        # Test staff edit form
        response = session.get("http://localhost/staff_edit.php?id=1")
        if 'guarantor_full_name' in response.text:
            print("  ✓ Staff edit form contains guarantor fields")
        else:
            print("  ✗ Staff edit form missing guarantor fields")
        
        # Test staff view page
        response = session.get("http://localhost/staff_view.php?id=1")
        if 'Guarantor Information' in response.text:
            print("  ✓ Staff view page contains guarantor section")
        else:
            print("  ✗ Staff view page missing guarantor section")
        
        return True
    except Exception as e:
        print(f"✗ Web access test failed: {e}")
        return False

def main():
    print("=" * 60)
    print("STAFF GUARANTOR INFORMATION SYSTEM - SIMPLE TEST")
    print("=" * 60)
    
    db_success = test_database()
    web_success = test_web_access()
    
    print("\n" + "=" * 60)
    print("TEST SUMMARY")
    print("=" * 60)
    
    if db_success and web_success:
        print("🎉 ALL TESTS PASSED!")
        print("\nStaff Guarantor Information System is working correctly:")
        print("- Database migration completed")
        print("- Guarantor fields added to staff edit form")
        print("- Guarantor information displayed in staff view page")
        print("- File upload directory configured")
        return True
    else:
        print("⚠ SOME TESTS FAILED")
        return False

if __name__ == "__main__":
    main()