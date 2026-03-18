#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
SSC.GOV.IN Website Monitor
Tracks updates, notices, and exams from Staff Selection Commission website
Uses direct API calls for real-time data
"""
import sys
import io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

import requests
import json
from datetime import datetime
import hashlib

class SSCMonitor:
    """Monitor for ssc.gov.in website updates"""

    BASE_URL = "https://ssc.gov.in"

    def __init__(self):
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept': 'application/json, text/plain, */*',
            'Referer': self.BASE_URL
        })

    def fetch_all_exams(self):
        """Fetch all active exams"""
        url = f"{self.BASE_URL}/api/admin/5.1/allExams"
        response = self.session.get(url, timeout=30)
        if response.status_code == 200:
            data = response.json()
            return data if isinstance(data, list) else []
        return []

    def fetch_notice_boards(self, limit=50):
        """Fetch notice board updates"""
        params = {
            'page': 1,
            'limit': limit,
            'contentType': 'notice-boards',
            'key': 'createdAt',
            'order': 'DESC',
            'isAttachment': 'true',
            'language': 'english',
            'attributes': 'id,headline,examId,contentType,redirectUrl,startDate,endDate,language,createdAt'
        }
        url = f"{self.BASE_URL}/api/general-website/portal/notice-boards"
        response = self.session.get(url, params=params, timeout=30)
        if response.status_code == 200:
            data = response.json()
            if data.get('statusCode') == '200':
                return data.get('data', [])
        return []

    def fetch_browse_exams(self, limit=50):
        """Fetch browse exam updates"""
        params = {
            'page': 1,
            'limit': limit,
            'contentType': 'browse-exam',
            'key': 'createdAt',
            'order': 'DESC',
            'isPaginationRequired': 'false',
            'isAttachment': 'true',
            'language': 'english',
            'attributes': 'id,headline,examId,contentType,startDate,endDate,language,createdAt'
        }
        url = f"{self.BASE_URL}/api/general-website/portal/records"
        response = self.session.get(url, params=params, timeout=30)
        if response.status_code == 200:
            data = response.json()
            if data.get('statusCode') == '200':
                return data.get('data', [])
        return []

    def fetch_ribbons(self):
        """Fetch important announcements (ribbons)"""
        params = {
            'page': 1,
            'limit': 10,
            'contentType': 'ribbons',
            'key': 'createdAt',
            'order': 'DESC',
            'isAttachment': 'false',
            'language': 'english',
            'attributes': 'id,headline,examId,contentType,redirectUrl,startDate,endDate,language,createdAt'
        }
        url = f"{self.BASE_URL}/api/general-website/portal/records"
        response = self.session.get(url, params=params, timeout=30)
        if response.status_code == 200:
            data = response.json()
            if data.get('statusCode') == '200':
                return data.get('data', [])
        return []

    def fetch_calendar(self, year=2026):
        """Fetch SSC exam calendar"""
        params = {
            'page': 1,
            'limit': 50,
            'contentType': 'ssc-calendar',
            'key': 'startDate',
            'order': 'ASC',
            'isAttachment': 'true',
            'isPaginationRequired': 'false',
            'language': 'english',
            'attributes': 'id,headline,examId,examYear,desc,content,contentType,startDate,endDate,language,createdAt',
            'year': year
        }
        url = f"{self.BASE_URL}/api/general-website/portal/ssc-calendar"
        response = self.session.get(url, params=params, timeout=30)
        if response.status_code == 200:
            data = response.json()
            if data.get('statusCode') == '200':
                return data.get('data', [])
        return []

    def get_all_updates(self):
        """Fetch all updates from SSC website"""
        print("[*] Fetching updates from ssc.gov.in...\n")

        results = {
            'fetched_at': datetime.now().isoformat(),
            'notices': [],
            'exams': [],
            'announcements': [],
            'calendar': [],
            'all_exams': []
        }

        # Fetch notices
        print("[1/5] Fetching Notice Boards...")
        notices = self.fetch_notice_boards(limit=100)
        results['notices'] = notices
        print(f"      Found: {len(notices)} notices")

        # Fetch browse exams
        print("[2/5] Fetching Browse Exam Updates...")
        browse_exams = self.fetch_browse_exams(limit=100)
        results['exams'] = browse_exams
        print(f"      Found: {len(browse_exams)} exam updates")

        # Fetch announcements
        print("[3/5] Fetching Important Announcements...")
        ribbons = self.fetch_ribbons()
        results['announcements'] = ribbons
        print(f"      Found: {len(ribbons)} announcements")

        # Fetch calendar
        print("[4/5] Fetching Exam Calendar...")
        calendar = self.fetch_calendar()
        results['calendar'] = calendar
        print(f"      Found: {len(calendar)} calendar entries")

        # Fetch all exams
        print("[5/5] Fetching All Active Exams...")
        all_exams = self.fetch_all_exams()
        results['all_exams'] = all_exams
        print(f"      Found: {len(all_exams)} active exams")

        return results

    def display_results(self, results):
        """Display the monitoring results"""
        print("\n" + "=" * 80)
        print("SSC.GOV.IN WEBSITE MONITORING RESULTS")
        print("=" * 80)

        # Summary
        total_items = (
            len(results['notices']) +
            len(results['exams']) +
            len(results['announcements']) +
            len(results['calendar']) +
            len(results['all_exams'])
        )
        print(f"\nTotal Updates Found: {total_items}")
        print(f"Fetched At: {results['fetched_at']}")

        # Notice Boards
        if results['notices']:
            print(f"\n{'=' * 80}")
            print(f"NOTICE BOARDS ({len(results['notices'])})")
            print('=' * 80)
            for i, notice in enumerate(results['notices'][:20], 1):
                headline = notice.get('headline', 'N/A')
                created = notice.get('createdAt', '')[:10]
                print(f"{i}. [{created}] {headline[:80]}")

        # Exam Updates
        if results['exams']:
            print(f"\n{'=' * 80}")
            print(f"EXAM UPDATES ({len(results['exams'])})")
            print('=' * 80)
            for i, exam in enumerate(results['exams'][:20], 1):
                headline = exam.get('headline', 'N/A')
                created = exam.get('createdAt', '')[:10]
                print(f"{i}. [{created}] {headline[:80]}")

        # Important Announcements
        if results['announcements']:
            print(f"\n{'=' * 80}")
            print(f"IMPORTANT ANNOUNCEMENTS ({len(results['announcements'])})")
            print('=' * 80)
            for i, ann in enumerate(results['announcements'], 1):
                headline = ann.get('headline', 'N/A')
                print(f"{i}. {headline[:80]}")

        # Calendar
        if results['calendar']:
            print(f"\n{'=' * 80}")
            print(f"EXAM CALENDAR ({len(results['calendar'])})")
            print('=' * 80)
            for i, cal in enumerate(results['calendar'][:15], 1):
                headline = cal.get('headline', 'N/A')
                start_date = cal.get('startDate', 'N/A')[:10]
                print(f"{i}. [{start_date}] {headline[:70]}")

        # All Active Exams
        if results['all_exams']:
            print(f"\n{'=' * 80}")
            print(f"ACTIVE EXAMS ({len(results['all_exams'])})")
            print('=' * 80)
            for i, exam in enumerate(results['all_exams'], 1):
                code = exam.get('examCode', 'N/A')
                name = exam.get('examName', 'N/A')
                print(f"{i}. [{code}] {name[:70]}")

        print("\n" + "=" * 80)

    def save_results(self, results, filename='ssc_updates.json'):
        """Save results to JSON file"""
        with open(filename, 'w', encoding='utf-8') as f:
            json.dump(results, f, indent=2, ensure_ascii=False)
        print(f"\n[*] Results saved to: {filename}")

    def check_new_updates(self, previous_file='ssc_updates_previous.json'):
        """Check for new updates since last run"""
        try:
            with open(previous_file, 'r', encoding='utf-8') as f:
                previous = json.load(f)

            current = self.get_all_updates()

            new_notices = []
            new_exams = []

            # Check for new notices
            prev_notice_ids = {n.get('id') for n in previous.get('notices', [])}
            for notice in current['notices']:
                if notice.get('id') not in prev_notice_ids:
                    new_notices.append(notice)

            # Check for new exam updates
            prev_exam_ids = {e.get('id') for e in previous.get('exams', [])}
            for exam in current['exams']:
                if exam.get('id') not in prev_exam_ids:
                    new_exams.append(exam)

            return {
                'new_notices': new_notices,
                'new_exams': new_exams,
                'total_new': len(new_notices) + len(new_exams)
            }
        except FileNotFoundError:
            print("[*] No previous data found. Creating baseline...")
            results = self.get_all_updates()
            self.save_results(results, previous_file)
            return {'new_notices': [], 'new_exams': [], 'total_new': 0}


# ============ MAIN ============
if __name__ == "__main__":
    print("""
╔════════════════════════════════════════════════════════════╗
║        SSC.GOV.IN WEBSITE MONITOR                         ║
║        Track notices, exams, and updates                  ║
╚════════════════════════════════════════════════════════════╝
    """)

    monitor = SSCMonitor()

    # Fetch all updates
    results = monitor.get_all_updates()

    # Display results
    monitor.display_results(results)

    # Save results
    monitor.save_results(results, 'C:/Users/Shamiksha/AppData/Local/Temp/ssc_updates_latest.json')
    monitor.save_results(results, 'C:/Users/Shamiksha/AppData/Local/Temp/ssc_updates_previous.json')

    print("\n[*] Monitoring complete!")
    print("[*] Run this script periodically to check for new updates")
