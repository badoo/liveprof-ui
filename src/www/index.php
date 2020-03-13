<?php declare(strict_types=1);

ini_set('display_errors', '0');

$vendor_path = __DIR__ . '/../../vendor/autoload.php';
$use_as_library_vendor_path = __DIR__ . '/../../../../../vendor/autoload.php';
if (file_exists($vendor_path)) {
    require_once $vendor_path;
} elseif (file_exists($use_as_library_vendor_path)) {
    require_once $use_as_library_vendor_path;
}

$App = new \Badoo\LiveProfilerUI\LiveProfilerUI();
$Logger = $App->getLogger();

switch (getCurrentUri()) {
    case '/profiler/list-view.phtml':
        $data = [
            'app' => isset($_GET['app']) ? trim($_GET['app']) : '',
            'label' => isset($_GET['label']) ? trim($_GET['label']) : '',
            'snapshot_id' => isset($_GET['snapshot_id']) ? (int)$_GET['snapshot_id'] : '',
            'all' => isset($_GET['all']) ? trim($_GET['all']) : '',
        ];
        $Page = $App->getPage('profile_method_list_page');
        echo $Page->setData($data)->render();
        break;

    case '/profiler/tree-view.phtml':
        $data = [
            'app' => isset($_GET['app']) ? trim($_GET['app']) : '',
            'label' => isset($_GET['label']) ? trim($_GET['label']) : '',
            'snapshot_id' => isset($_GET['snapshot_id']) ? (int)$_GET['snapshot_id'] : '',
            'method_id' => isset($_GET['method_id']) ? (int)$_GET['method_id'] : 0,
            'method_name' => isset($_GET['method_name']) ? trim($_GET['method_name']) : '',
            'stat_interval' => isset($_GET['stat_interval']) ? (int)$_GET['stat_interval'] : '',
            'date1' => isset($_GET['date1']) ? trim($_GET['date1']) : '',
            'date2' => isset($_GET['date2']) ? trim($_GET['date2']) : '',
        ];
        $Page = $App->getPage('profile_method_tree_page');
        echo $Page->setData($data)->render();
        break;

    case '/profiler/result-flamegraph.phtml':
        $data = [
            'app' => isset($_GET['app']) ? trim($_GET['app']) : '',
            'label' => isset($_GET['label']) ? trim($_GET['label']) : '',
            'snapshot_id' => isset($_GET['snapshot_id']) ? (int)$_GET['snapshot_id'] : '',
            'param' => isset($_GET['param']) ? trim($_GET['param']) : '',
            'diff' => isset($_GET['diff']) ? trim($_GET['diff']) : '',
            'date' => isset($_GET['date']) ? trim($_GET['date']) : '',
            'date1' => isset($_GET['date1']) ? trim($_GET['date1']) : '',
            'date2' => isset($_GET['date2']) ? trim($_GET['date2']) : '',
        ];
        $Page = $App->getPage('flame_graph_page');
        echo $Page->setData($data)->render();
        break;

    case '/profiler/result-diff.phtml':
        $data = [
            'app' => isset($_GET['app']) ? trim($_GET['app']) : '',
            'label' => isset($_GET['label']) ? trim($_GET['label']) : '',
            'date1' => isset($_GET['date1']) ? trim($_GET['date1']) : '',
            'date2' => isset($_GET['date2']) ? trim($_GET['date2']) : '',
            'param' => isset($_GET['param']) ? trim($_GET['param']) : '',
        ];
        $Page = $App->getPage('snapshots_diff_page');
        echo $Page->setData($data)->render();
        break;

    case '/profiler/method-usage.phtml':
        $data = [
            'method' => isset($_GET['method']) ? trim($_GET['method']) : ''
        ];
        $Page = $App->getPage('method_usage_page');
        echo $Page->setData($data)->render();
        break;

    case '/profiler/top-diff.phtml':
        $data = [
            'date1' => isset($_GET['date1']) ? trim($_GET['date1']) : date('Y-m-d', strtotime('-3 months')),
            'date2' => isset($_GET['date2']) ? trim($_GET['date2']) : date('Y-m-d', strtotime('-1 day')),
            'param' => isset($_GET['param']) ? trim($_GET['param']) : '',
            'mode' => isset($_GET['mode']) ? trim($_GET['mode']) : 'snapshots',
        ];
        $Page = $App->getPage('top_diff_page');
        echo $Page->setData($data)->render();
        break;

    case '/profiler/rebuild-snapshot.json':
        $app = isset($_POST['app']) ? trim($_POST['app']) : '';
        $label = isset($_POST['label']) ? trim($_POST['label']) : '';
        $date = isset($_POST['date']) ? trim($_POST['date']) : date('Y-m-d');
        header('Content-Type: application/json;charset=UTF-8');

        /** @var \Badoo\LiveProfilerUI\Pages\AjaxPages $Page */
        $Page = $App->getPage('ajax_pages');
        echo json_encode($Page->rebuildSnapshot($app, $label, $date));
        break;

    case '/profiler/check-snapshot.json':
        $app = isset($_POST['app']) ? trim($_POST['app']) : '';
        $label = isset($_POST['label']) ? trim($_POST['label']) : '';
        $date = isset($_POST['date']) ? trim($_POST['date']) : date('Y-m-d');
        header('Content-Type: application/json;charset=UTF-8');

        /** @var \Badoo\LiveProfilerUI\Pages\AjaxPages $Page */
        $Page = $App->getPage('ajax_pages');
        echo json_encode($Page->checkSnapshot($app, $label, $date));
        break;

    case '/profiler/search-method.json':
        $term = isset($_POST['term']) ? trim($_POST['term']) : '';
        header('Content-Type: application/json;charset=UTF-8');

        /** @var \Badoo\LiveProfilerUI\Pages\AjaxPages $Page */
        $Page = $App->getPage('ajax_pages');
        $methods = $Page->searchMethods($term);

        $result = [];
        foreach ($methods as $method_id => $method) {
            $result[] = [
                'label' => $method['name'] . ' - ' . $method['date'],
                'value' => $method['name'],
            ];
        }

        echo json_encode($result);
        break;

    case '/profiler/method-used-apps.json':
        $method = isset($_GET['method']) ? trim($_GET['method']) : '';
        header('Content-Type: application/json;charset=UTF-8');

        /** @var \Badoo\LiveProfilerUI\Pages\AjaxPages $Page */
        $Page = $App->getPage('ajax_pages');
        $method_data = $Page->getMethodUsedApps($method);

        $result = [];
        if ($method_data) {
            foreach ($method_data as $item) {
                if (empty($item['fields']) || empty($item['fields']['calls_count'])) {
                    continue;
                }

                $item['cpu'] = $item['fields']['cpu'] / 1000;
                $item['ct'] = $item['fields']['ct'];
                $item['calls_count'] = (int)$item['fields']['calls_count'];
                unset($item['fields']);
                // Keep 1 app with maximum cpu
                if (isset($result[$item['app']]) && $result[$item['app']]['cpu'] > $item['cpu']) {
                    continue;
                }
                $result[$item['app']] = $item;
            }

            uasort(
                $result,
                static function ($a, $b) {
                    return $b['cpu'] <=> $a['cpu'];
                }
            );

            $result = array_values($result);
        }

        echo json_encode($result);
        break;

    case '/profiler/all-methods.json':
        header('Content-Type: application/json;charset=UTF-8');

        /** @var \Badoo\LiveProfilerUI\Pages\AjaxPages $Page */
        $Page = $App->getPage('ajax_pages');

        echo json_encode($Page->allMethods());
        break;

    case '/profiler/get-source-app-list.json':
        header('Content-Type: application/json;charset=UTF-8');

        /** @var \Badoo\LiveProfilerUI\Pages\AjaxPages $Page */
        $Page = $App->getPage('ajax_pages');
        echo json_encode($Page->getSourceAppList());
        break;

    case '/profiler/get-source-label-list.json':
        header('Content-Type: application/json;charset=UTF-8');

        /** @var \Badoo\LiveProfilerUI\Pages\AjaxPages $Page */
        $Page = $App->getPage('ajax_pages');
        echo json_encode($Page->getSourceLabelList($_GET['app']));
        break;

    case '/profiler/result-list.phtml':
    default:
        $data = [
            'app' => isset($_GET['app']) ? trim($_GET['app']) : '',
            'label' => isset($_GET['label']) ? trim($_GET['label']) : '',
            'date' => isset($_GET['date']) ? trim($_GET['date']) : '',
        ];
        $Page = $App->getPage('profile_list_page');
        echo $Page->setData($data)->render();
}

function getCurrentUri() : string
{
    return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
}
