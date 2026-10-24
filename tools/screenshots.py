#!/usr/bin/env python3
"""First-screen screenshots for the README, taken from the demo data.

    python3 tools/screenshots.py <locale> <output-dir>

Signs in as the demo system administrator, instructor and student (password `demo-password`,
created by `setup:demo`), switches the account language to <locale>, and asks the Gotenberg
container to render /admin and /portal. Expects Sail running on localhost:80 and Gotenberg on
localhost:3000 (both from compose.yaml). See docs/development/adding-a-language.md.
"""

import http.cookiejar
import json
import os
import re
import subprocess
import sys
import urllib.error
import urllib.parse
import urllib.request

APP = os.environ.get('APP_URL', 'http://localhost')
APP_HOST = os.environ.get('APP_HOST', 'laravel.test')  # how Gotenberg reaches the app inside the Sail network
GOTENBERG = os.environ.get('GOTENBERG_URL', 'http://localhost:3000')
PASSWORD = os.environ.get('DEMO_PASSWORD', 'demo-password')
ACCOUNTS = [
    ('system-administrator@demo.test', '/admin', 'admin.png'),
    ('instructor@demo.test', '/portal', 'instructor.png'),
    ('student@demo.test', '/portal', 'student.png'),
]


def post(opener, url, fields):
    try:
        opener.open(urllib.request.Request(url, data=urllib.parse.urlencode(fields).encode(), method='POST'))
    except urllib.error.HTTPError:
        pass  # redirects after POST are followed; other statuses are visible in the screenshot


def token_from(opener, url):
    html = opener.open(url).read().decode()
    match = re.search(r'name="_token" value="([^"]+)"', html) or re.search(r'csrf-token" content="([^"]+)"', html)
    return match.group(1)


def shot(locale, email, path, out):
    jar = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
    post(opener, f'{APP}/login', {'_token': token_from(opener, f'{APP}/login'), 'email': email, 'password': PASSWORD})
    post(opener, f'{APP}/locale', {'_token': token_from(opener, f'{APP}/portal'), 'locale': locale})
    cookies = [{'name': c.name, 'value': c.value, 'domain': APP_HOST, 'path': '/'} for c in jar]
    result = subprocess.run([
        'curl', '-s', '-o', out, '-w', '%{http_code}', '-X', 'POST', f'{GOTENBERG}/forms/chromium/screenshot/url',
        '-F', f'url=http://{APP_HOST}{path}', '-F', 'width=1366', '-F', 'height=900', '-F', 'format=png',
        '-F', 'waitDelay=2s', '-F', 'cookies=' + json.dumps(cookies),
    ], capture_output=True, text=True)
    print(f'{email} {path} -> {out} ({result.stdout})')


def main():
    if len(sys.argv) != 3:
        print(__doc__)
        sys.exit(2)
    locale, directory = sys.argv[1], sys.argv[2]
    os.makedirs(directory, exist_ok=True)
    for email, path, name in ACCOUNTS:
        shot(locale, email, path, os.path.join(directory, name))


if __name__ == '__main__':
    main()
