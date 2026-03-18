#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Generic Website Monitor for Government Sites
Tracks multiple websites for updates using APIs or web scraping
"""
import sys
import io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

import requests
import json
from datetime import datetime
from pathlib import Path

class GenericWebsiteMonitor:
    """Monitor for government websites with API support"""

    def __init__(self, config_file='websites.json'):
        self.config_file = config_file
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        })
        self.websites = self.load_config()

    def load_config(self):
        """Load website configurations"""
        default_config = {
            "websites": [
                {
                    "name": "SSC - Staff Selection Commission",
                    "url": "https://ssc.gov.in",
                    "type": "ssc_api",
                    "enabled": True
                },
                {
                    "name": "PIB - Press Information Bureau",
                    "url": "https://pib.gov.in",
                    "type": "rss",
                    "enabled": True
                },
                {
                    "name": "India.gov.in",
                    "url": "https://www.india.gov.in",
                    "type": "scrape",
                    "enabled": True
                }
            ]
        }

        if Path(self.config_file).exists():
            with open(self.config_file, 'r', encoding='utf-8') as f:
                config = json.load(f)
                return config.get('websites', default_config['websites'])
        return default_config['websites']

    def monitor_ssc(self, url):
        """Monitor SSC.gov.in using their API"""
        base_url = url.rstrip('/')

        results = {
            'name': 'SSC',
            'url': url,
            'notices': [],
            'exams': [],
            'announcements': []
        }

        try:
            # Notice Boards
            params = {
                'page': 1, 'limit': 50, 'contentType': 'notice-boards',
                'key': 'createdAt', 'order': 'DESC', 'isAttachment': 'true',
                'language': 'english',
                'attributes': 'id,headline,examId,contentType,redirectUrl,startDate,endDate,language,createdAt'
            }
            response = self.session.get(f"{base_url}/api/general-website/portal/notice-boards", params=params, timeout=30)
            if response.status_code == 200:
                data = response.json()
                if data.get('statusCode') == '200':
                    results['notices'] = data.get('data', [])

            # Browse Exams
            params['contentType'] = 'browse-exam'
            response = self.session.get(f"{base_url}/api/general-website/portal/records", params=params, timeout=30)
            if response.status_code == 200:
                data = response.json()
                if data.get('statusCode') == '200':
                    results['exams'] = data.get('data', [])

            # Announcements (Ribbons)
            params['contentType'] = 'ribbons'
            params['isAttachment'] = 'false'
            response = self.session.get(f"{base_url}/api/general-website/portal/records", params=params, timeout=30)
            if response.status_code == 200:
                data = response.json()
                if data.get('statusCode') == '200':
                    results['announcements'] = data.get('data', [])

        except Exception as e:
            print(f"      Error: {e}")

        return results

    def monitor_rss(self, url):
        """Monitor website with RSS feed"""
        try:
            # Try common RSS paths
            rss_paths = ['/rss', '/feed', '/rss.xml', '/feed.xml']
            for path in rss_paths:
                response = self.session.get(url.rstrip('/') + path, timeout=15)
                if response.status_code == 200 and 'rss' in response.text.lower():
                    import xml.etree.ElementTree as ET
                    root = ET.fromstring(response.text)
                    items = []
                    for item in root.findall('.//item')[:20]:
                        title = item.find('title')
                        link = item.find('link')
                        pubDate = item.find('pubDate')
                        items.append({
                            'title': title.text if title is not None else '',
                            'url': link.text if link is not None else '',
                            'date': pubDate.text if pubDate is not None else ''
                        })
                    return {'name': url, 'url': url, 'items': items}
        except Exception as e:
            print(f"      RSS Error: {e}")

        return {'name': url, 'url': url, 'items': []}

    def monitor_scrape(self, url):
        """Basic web scraping for updates"""
        from bs4 import BeautifulSoup

        results = {'name': url, 'url': url, 'links': [], 'headlines': []}

        try:
            response = self.session.get(url, timeout=30)
            if response.status_code == 200:
                soup = BeautifulSoup(response.text, 'html.parser')

                # Get links with news/notice keywords
                for link in soup.find_all('a', href=True):
                    text = link.get_text(strip=True).lower()
                    if any(kw in text for kw in ['notice', 'news', 'update', 'announcement']):
                        results['links'].append({
                            'text': link.get_text(strip=True),
                            'url': link['href']
                        })

                # Get headlines
                for h in soup.find_all(['h1', 'h2', 'h3'])[:30]:
                    text = h.get_text(strip=True)
                    if len(text) > 10 and len(text) < 200:
                        results['headlines'].append(text)

        except Exception as e:
            print(f"      Scrape Error: {e}")

        return results

    def monitor_all(self):
        """Monitor all configured websites"""
        all_results = {
            'timestamp': datetime.now().isoformat(),
            'websites': []
        }

        print("=" * 70)
        print("WEBSITE MONITOR - Checking all configured websites")
        print("=" * 70)

        for site in self.websites:
            if not site.get('enabled', True):
                continue

            print(f"\n[*] Monitoring: {site['name']}")
            print(f"    URL: {site['url']}")

            site_type = site.get('type', 'scrape')
            results = None

            if site_type == 'ssc_api':
                results = self.monitor_ssc(site['url'])
            elif site_type == 'rss':
                results = self.monitor_rss(site['url'])
            else:
                results = self.monitor_scrape(site['url'])

            # Summarize results
            if results:
                count = 0
                if 'notices' in results:
                    count += len(results['notices'])
                    print(f"    Notices: {len(results['notices'])}")
                if 'exams' in results:
                    count += len(results['exams'])
                    print(f"    Exam Updates: {len(results['exams'])}")
                if 'announcements' in results:
                    count += len(results['announcements'])
                    print(f"    Announcements: {len(results['announcements'])}")
                if 'items' in results:
                    count += len(results['items'])
                    print(f"    RSS Items: {len(results['items'])}")
                if 'links' in results:
                    count += len(results['links'])
                    print(f"    Links Found: {len(results['links'])}")

                print(f"    Total Updates: {count}")
                all_results['websites'].append(results)

        return all_results

    def display_results(self, results):
        """Display monitoring results"""
        print("\n" + "=" * 70)
        print("MONITORING SUMMARY")
        print("=" * 70)

        for site in results['websites']:
            name = site.get('name', site.get('url', 'Unknown'))
            print(f"\n{name}:")

            if 'notices' in site and site['notices']:
                print(f"  Notices ({len(site['notices'])}):")
                for notice in site['notices'][:5]:
                    headline = notice.get('headline', 'N/A')
                    print(f"    - {headline[:70]}")

            if 'exams' in site and site['exams']:
                print(f"  Exam Updates ({len(site['exams'])}):")
                for exam in site['exams'][:3]:
                    headline = exam.get('headline', 'N/A')
                    print(f"    - {headline[:70]}")

            if 'items' in site and site['items']:
                print(f"  RSS Feed ({len(site['items'])}):")
                for item in site['items'][:5]:
                    print(f"    - {item.get('title', 'N/A')[:70]}")

            if 'links' in site and site['links']:
                print(f"  Links ({len(site['links'])}):")
                for link in site['links'][:5]:
                    print(f"    - {link.get('text', 'N/A')[:70]}")

        print("\n" + "=" * 70)

    def save_results(self, results, save_dir=None):
        """Save results to file"""
        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        if save_dir is None:
            save_dir = 'C:/Users/Shamiksha/AppData/Local/Temp'
        filename = f"{save_dir}/monitoring_results_{timestamp}.json"

        with open(filename, 'w', encoding='utf-8') as f:
            json.dump(results, f, indent=2, ensure_ascii=False)

        print(f"\n[*] Results saved to: {filename}")


# ============ MAIN ============
if __name__ == "__main__":
    print("""
╔════════════════════════════════════════════════════════════╗
║        GENERIC WEBSITE MONITOR                            ║
║        Track multiple government websites                 ║
╚════════════════════════════════════════════════════════════╝
    """)

    monitor = GenericWebsiteMonitor()

    # Run monitoring
    results = monitor.monitor_all()

    # Display and save
    monitor.display_results(results)
    monitor.save_results(results)

    print("\n[*] To add more websites, edit 'websites.json'")
    print("[*] Monitor complete!")
