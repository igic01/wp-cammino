"""Private legacy storage and record preservation while switching disposable themes.

Run alone: this temporarily changes the test installation's active theme.
"""
import tempfile
import http_support as support
from http_support import *

fixtures = fixture('setup')
prefix = fixtures['prefix']
original_theme = None
with tempfile.TemporaryDirectory(prefix='cammino-theme-check-') as temp:
    helper_path = Path(temp) / 'switch.php'
    helper_path.write_text('''<?php
if ('cli' !== PHP_SAPI || empty($argv[1]) || !is_file($argv[1])) { exit(1); }
ob_start(); $_SERVER['HTTP_HOST']='127.0.0.1:8765'; require $argv[1];
if (!defined('CAMMINO_TIPSTERS_TEST_INSTALLATION') || true !== CAMMINO_TIPSTERS_TEST_INSTALLATION) { exit(1); }
if (!empty($argv[2])) {
    $theme=wp_get_theme($argv[2]); if (!$theme->exists()) { exit(1); }
    switch_theme($argv[2]);
}
$result=array('theme'=>get_stylesheet());
if (!empty($argv[3])) { $post=get_post((int)$argv[3]); $result['tip_exists']=$post && 'private' === $post->post_status; }
ob_end_clean(); echo wp_json_encode($result);
''', encoding='utf-8')

    def theme(name='', tip_id=''):
        return json.loads(subprocess.check_output([args.php, str(helper_path), args.wp_load, name, tip_id], text=True, encoding='utf-8'))

    try:
        original_theme = theme()['theme']
        owner, guest = session(), session()
        login(owner, fixtures['users']['one']['username'], fixtures['password'])
        form = new_form(owner); form.update(title='Private theme-switch fixture', short_description='Short', long_description='Long')
        tip = submit_tip(owner, form)
        tip_id = re.search(r'/tip/(\d+)/', tip[1]).group(1)
        legacy = fixture('legacy', prefix)
        stored = fixture('snapshot', prefix)['one']['files'][0]
        disk_path = Path(fixtures['storage_root']) / stored['path']
        wp_root = Path(args.wp_load).resolve().parent
        check(wp_root not in disk_path.resolve().parents, 'Configured private storage is outside served WordPress root')
        check(request(owner, legacy['url'])[3].decode() == legacy['body'], 'Authenticated legacy download works before theme change')
        for target in [original_theme, 'twentytwentyfour', original_theme]:
            result = theme(target, tip_id)
            check(result['theme'] == target and result['tip_exists'] and disk_path.is_file(), 'Theme changes preserve private tip and physical file')
            for path in ['/private-files/' + stored['path'], '/wp-content/uploads/' + stored['path'], '/?p=' + tip_id]:
                response = request(guest, path)
                check(legacy['body'].encode() not in response[3] and b'Private theme-switch fixture' not in response[3], 'Anonymous direct/public access remains isolated through theme change')
        login(owner, fixtures['users']['one']['username'], fixtures['password'])
        check(request(owner, legacy['url'])[3].decode() == legacy['body'] and request(owner, tip[1])[0] == 200, 'Reactivation restores authenticated access to preserved records')
        print(f'Passed {support.checks} local storage/theme-switch HTTP checks.')
    finally:
        if original_theme:
            theme(original_theme)
        fixture('cleanup', prefix)
