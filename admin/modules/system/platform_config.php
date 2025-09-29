<?php
/**
 * 平台配置管理页面
 * 用于配置各大平台的API参数和发布设置
 */

// 设置绝对路径
define('BASE_DIR', dirname(dirname(dirname(dirname(__FILE__)))));

// 会话初始化
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 检查是否已登录
if (!isset($_SESSION['admin_id']) || !$_SESSION['admin_id']) {
    header('Location: ../../login.php');
    exit;
}

// 引入配置文件
require_once BASE_DIR . '/includes/config.php';
require_once BASE_DIR . '/includes/database.php';
require_once BASE_DIR . '/includes/functions.php';
require_once BASE_DIR . '/includes/PlatformManager.php';

// 检查管理员权限
check_admin_auth();

// 初始化数据库连接
$dbInstance = Database::getInstance();
$conn = $dbInstance->getConnection();

// 初始化平台管理器
$platform_manager = new PlatformManager($conn);

// 获取所有平台配置
$platforms = $platform_manager->getAllPlatforms();

// 初始化消息变量
$success_msg = '';
$error_msg = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_msg = 'CSRF验证失败，请刷新页面重试';
    } else {
        try {
            // 获取平台key
            $platform_key = $_POST['platform_key'] ?? '';
            
            if (empty($platform_key)) {
                throw new Exception('平台标识不能为空');
            }
            
            // 构建配置数据
            $config_data = [
                'api_key' => $_POST['api_key'] ?? '',
                'api_secret' => $_POST['api_secret'] ?? '',
                'access_token' => $_POST['access_token'] ?? '',
                'refresh_token' => $_POST['refresh_token'] ?? '',
                'status' => isset($_POST['enabled']) ? 1 : 0
            ];
            
            // 处理特定平台的额外配置
            $additional_config = [];
            switch ($platform_key) {
                case 'wechat':
                    $additional_config['app_id'] = $_POST['app_id'] ?? '';
                    $additional_config['app_secret'] = $_POST['app_secret'] ?? '';
                    $additional_config['token'] = $_POST['wechat_token'] ?? '';
                    break;
                case 'toutiao':
                    $additional_config['client_key'] = $_POST['client_key'] ?? '';
                    $additional_config['client_secret'] = $_POST['client_secret'] ?? '';
                    break;
                case 'zhihu':
                    $additional_config['client_id'] = $_POST['client_id'] ?? '';
                    $additional_config['client_secret'] = $_POST['client_secret'] ?? '';
                    break;
                case 'bilibili':
                    $additional_config['app_key'] = $_POST['app_key'] ?? '';
                    $additional_config['app_secret'] = $_POST['app_secret'] ?? '';
                    break;
            }
            
            // 添加通用配置
            $additional_config['publish_type'] = $_POST['publish_type'] ?? 'auto';
            $additional_config['title_template'] = $_POST['title_template'] ?? '';
            $additional_config['content_template'] = $_POST['content_template'] ?? '';
            
            $config_data['config'] = $additional_config;
            
            // 更新平台配置
            if ($platform_manager->updatePlatform($platform_key, $config_data)) {
                $success_msg = '平台配置更新成功！';
            } else {
                throw new Exception('更新配置失败');
            }
            
        } catch(Exception $e) {
            $error_msg = '操作失败：' . $e->getMessage();
        }
    }
}

// 生成CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = generate_token();
}

// 定义平台特定的配置字段
$platform_specific_fields = [
    'douyin' => [
        ['name' => 'api_key', 'label' => 'API密钥', 'type' => 'text', 'required' => true],
        ['name' => 'api_secret', 'label' => 'API密钥', 'type' => 'password', 'required' => true],
        ['name' => 'access_token', 'label' => '访问令牌', 'type' => 'text', 'required' => true],
        ['name' => 'refresh_token', 'label' => '刷新令牌', 'type' => 'text']
    ],
    'kuaishou' => [
        ['name' => 'client_key', 'label' => 'Client Key', 'type' => 'text', 'required' => true],
        ['name' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password', 'required' => true],
        ['name' => 'access_token', 'label' => '访问令牌', 'type' => 'text', 'required' => true],
        ['name' => 'refresh_token', 'label' => '刷新令牌', 'type' => 'text']
    ],
    'xiaohongshu' => [
        ['name' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'required' => true],
        ['name' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password', 'required' => true],
        ['name' => 'access_token', 'label' => '访问令牌', 'type' => 'text', 'required' => true],
        ['name' => 'refresh_token', 'label' => '刷新令牌', 'type' => 'text']
    ],
    'wechat' => [
        ['name' => 'app_id', 'label' => 'AppID', 'type' => 'text', 'required' => true],
        ['name' => 'app_secret', 'label' => 'AppSecret', 'type' => 'password', 'required' => true],
        ['name' => 'wechat_token', 'label' => '服务器配置Token', 'type' => 'text']
    ],
    'toutiao' => [
        ['name' => 'client_key', 'label' => 'Client Key', 'type' => 'text', 'required' => true],
        ['name' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password', 'required' => true],
        ['name' => 'access_token', 'label' => '访问令牌', 'type' => 'text', 'required' => true],
        ['name' => 'refresh_token', 'label' => '刷新令牌', 'type' => 'text']
    ],
    'baidu' => [
        ['name' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'required' => true],
        ['name' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password', 'required' => true],
        ['name' => 'access_token', 'label' => '访问令牌', 'type' => 'text', 'required' => true],
        ['name' => 'refresh_token', 'label' => '刷新令牌', 'type' => 'text']
    ],
    'zhihu' => [
        ['name' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'required' => true],
        ['name' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password', 'required' => true],
        ['name' => 'access_token', 'label' => '访问令牌', 'type' => 'text', 'required' => true],
        ['name' => 'refresh_token', 'label' => '刷新令牌', 'type' => 'text']
    ],
    'bilibili' => [
        ['name' => 'app_key', 'label' => 'App Key', 'type' => 'text', 'required' => true],
        ['name' => 'app_secret', 'label' => 'App Secret', 'type' => 'password', 'required' => true],
        ['name' => 'access_token', 'label' => '访问令牌', 'type' => 'text', 'required' => true],
        ['name' => 'refresh_token', 'label' => '刷新令牌', 'type' => 'text']
    ],
    'weibo' => [
        ['name' => 'app_key', 'label' => 'App Key', 'type' => 'text', 'required' => true],
        ['name' => 'app_secret', 'label' => 'App Secret', 'type' => 'password', 'required' => true],
        ['name' => 'access_token', 'label' => '访问令牌', 'type' => 'text', 'required' => true]
    ],
    'douban' => [
        ['name' => 'api_key', 'label' => 'API Key', 'type' => 'text', 'required' => true],
        ['name' => 'api_secret', 'label' => 'Secret Key', 'type' => 'password', 'required' => true],
        ['name' => 'access_token', 'label' => '访问令牌', 'type' => 'text', 'required' => true]
    ],
    'kuaikan' => [
        ['name' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'required' => true],
        ['name' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password', 'required' => true],
        ['name' => 'access_token', 'label' => '访问令牌', 'type' => 'text', 'required' => true],
        ['name' => 'refresh_token', 'label' => '刷新令牌', 'type' => 'text']
    ],
    'yilan' => [
        ['name' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'required' => true],
        ['name' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password', 'required' => true],
        ['name' => 'access_token', 'label' => '访问令牌', 'type' => 'text', 'required' => true],
        ['name' => 'refresh_token', 'label' => '刷新令牌', 'type' => 'text']
    ],
    'dayu' => [
        ['name' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'required' => true],
        ['name' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password', 'required' => true],
        ['name' => 'access_token', 'label' => '访问令牌', 'type' => 'text', 'required' => true],
        ['name' => 'refresh_token', 'label' => '刷新令牌', 'type' => 'text']
    ],
    'chyxx' => [
        ['name' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'required' => true],
        ['name' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password', 'required' => true],
        ['name' => 'access_token', 'label' => '访问令牌', 'type' => 'text', 'required' => true],
        ['name' => 'refresh_token', 'label' => '刷新令牌', 'type' => 'text']
    ],
    'maoyan' => [
        ['name' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'required' => true],
        ['name' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password', 'required' => true],
        ['name' => 'access_token', 'label' => '访问令牌', 'type' => 'text', 'required' => true],
        ['name' => 'refresh_token', 'label' => '刷新令牌', 'type' => 'text']
    ],
    'alizhizhen' => [
        ['name' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'required' => true],
        ['name' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password', 'required' => true],
        ['name' => 'access_token', 'label' => '访问令牌', 'type' => 'text', 'required' => true],
        ['name' => 'refresh_token', 'label' => '刷新令牌', 'type' => 'text']
    ],
    'pengpai' => [
        ['name' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'required' => true],
        ['name' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password', 'required' => true],
        ['name' => 'access_token', 'label' => '访问令牌', 'type' => 'text', 'required' => true],
        ['name' => 'refresh_token', 'label' => '刷新令牌', 'type' => 'text']
    ],
    'huxiu' => [
        ['name' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'required' => true],
        ['name' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password', 'required' => true],
        ['name' => 'access_token', 'label' => '访问令牌', 'type' => 'text', 'required' => true],
        ['name' => 'refresh_token', 'label' => '刷新令牌', 'type' => 'text']
    ],
    'iyiou' => [
        ['name' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'required' => true],
        ['name' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password', 'required' => true],
        ['name' => 'access_token', 'label' => '访问令牌', 'type' => 'text', 'required' => true],
        ['name' => 'refresh_token', 'label' => '刷新令牌', 'type' => 'text']
    ],
    'tmtpost' => [
        ['name' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'required' => true],
        ['name' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password', 'required' => true],
        ['name' => 'access_token', 'label' => '访问令牌', 'type' => 'text', 'required' => true],
        ['name' => 'refresh_token', 'label' => '刷新令牌', 'type' => 'text']
    ]
];
?>
<?php
// 定义各平台获取API密钥的方法说明
$platform_api_guides = [
    'douyin' => [
        'name' => '抖音',
        'guide' => '
            <h4>获取抖音开放平台API密钥步骤：</h4>
            <ol>
                <li>访问 <a href="https://open.douyin.com/platform" target="_blank">抖音开放平台</a></li>
                <li>注册/登录开发者账号</li>
                <li>进入"应用管理"页面，创建一个新应用</li>
                <li>填写应用基本信息，选择"服务号"类型</li>
                <li>在"开发设置"中配置回调URL和JS接口安全域名</li>
                <li>在应用详情页获取AppID和AppSecret</li>
                <li>在"权限管理"中申请需要的权限，如"视频发布"等</li>
                <li>完成开发者资质认证</li>
            </ol>
            <p><strong>注意：</strong>抖音平台需要企业认证资质才能使用内容发布接口。</p>
        '
    ],
    'kuaishou' => [
        'name' => '快手',
        'guide' => '
            <h4>获取快手开放平台API密钥步骤：</h4>
            <ol>
                <li>访问 <a href="https://open.kuaishou.com" target="_blank">快手开放平台</a></li>
                <li>注册/登录开发者账号</li>
                <li>进入"控制台"，创建一个新应用</li>
                <li>选择应用类型为"服务号"</li>
                <li>填写应用基本信息和服务器配置</li>
                <li>在应用详情页获取Client Key和Client Secret</li>
                <li>配置应用权限，申请"内容发布"权限</li>
                <li>完成企业认证</li>
            </ol>
            <p><strong>注意：</strong>快手开放平台同样需要企业认证资质。</p>
        '
    ],
    'xiaohongshu' => [
        'name' => '小红书',
        'guide' => '
            <h4>获取小红书开放平台API密钥步骤：</h4>
            <ol>
                <li>访问 <a href="https://open.xiaohongshu.com" target="_blank">小红书开放平台</a></li>
                <li>注册/登录开发者账号</li>
                <li>进入"应用管理"，创建一个新应用</li>
                <li>选择应用类型为"服务号"</li>
                <li>填写应用基本信息</li>
                <li>在应用详情页获取Client ID和Client Secret</li>
                <li>配置应用权限，申请"内容发布"权限</li>
                <li>完成开发者资质认证</li>
            </ol>
            <p><strong>注意：</strong>小红书平台对内容质量和账号等级有一定要求。</p>
        '
    ],
    'wechat' => [
        'name' => '微信公众号',
        'guide' => '
            <h4>获取微信公众号API密钥步骤：</h4>
            <ol>
                <li>登录 <a href="https://mp.weixin.qq.com" target="_blank">微信公众平台</a></li>
                <li>进入"开发"->"基本配置"</li>
                <li>获取AppID(应用ID)和AppSecret(应用密钥)</li>
                <li>设置服务器配置中的URL、Token和EncodingAESKey</li>
                <li>在"开发者工具"中申请微信认证</li>
                <li>确保公众号已开通微信认证</li>
            </ol>
            <p><strong>注意：</strong>个人订阅号权限有限，建议使用服务号或企业号。</p>
        '
    ],
    'toutiao' => [
        'name' => '今日头条',
        'guide' => '
            <h4>获取今日头条开放平台API密钥步骤：</h4>
            <ol>
                <li>访问 <a href="https://open.snssdk.com" target="_blank">今日头条开放平台</a></li>
                <li>注册/登录开发者账号</li>
                <li>进入"应用管理"，创建一个新应用</li>
                <li>选择应用类型为"服务号"</li>
                <li>填写应用基本信息</li>
                <li>在应用详情页获取Client Key和Client Secret</li>
                <li>配置应用权限，申请"内容发布"权限</li>
                <li>完成企业认证</li>
            </ol>
            <p><strong>注意：</strong>头条平台需要企业认证资质。</p>
        '
    ],
    'baidu' => [
        'name' => '百家号',
        'guide' => '
            <h4>获取百家号API密钥步骤：</h4>
            <ol>
                <li>登录 <a href="https://baijiahao.baidu.com" target="_blank">百家号平台</a></li>
                <li>进入"开发者"->"开放平台"</li>
                <li>创建一个新应用</li>
                <li>填写应用基本信息</li>
                <li>在应用详情页获取Client ID和Client Secret</li>
                <li>配置应用权限</li>
                <li>完成企业认证</li>
            </ol>
            <p><strong>注意：</strong>百家号平台需要企业认证资质。</p>
        '
    ],
    'zhihu' => [
        'name' => '知乎',
        'guide' => '
            <h4>获取知乎开放平台API密钥步骤：</h4>
            <ol>
                <li>访问 <a href="https://open.zhihu.com" target="_blank">知乎开放平台</a></li>
                <li>注册/登录开发者账号</li>
                <li>进入"应用管理"，创建一个新应用</li>
                <li>选择应用类型</li>
                <li>填写应用基本信息</li>
                <li>在应用详情页获取Client ID和Client Secret</li>
                <li>配置应用权限，申请"内容发布"权限</li>
            </ol>
            <p><strong>注意：</strong>知乎平台对内容质量要求较高。</p>
        '
    ],
    'bilibili' => [
        'name' => 'B站',
        'guide' => '
            <h4>获取B站开放平台API密钥步骤：</h4>
            <ol>
                <li>访问 <a href="https://open.bilibili.com" target="_blank">B站开放平台</a></li>
                <li>注册/登录开发者账号</li>
                <li>进入"应用管理"，创建一个新应用</li>
                <li>填写应用基本信息</li>
                <li>在应用详情页获取App Key和App Secret</li>
                <li>配置应用权限</li>
                <li>完成开发者认证</li>
            </ol>
            <p><strong>注意：</strong>B站对UP主等级和内容有一定要求。</p>
        '
    ],
    'weibo' => [
        'name' => '新浪微博',
        'guide' => '
            <h4>获取新浪微博开放平台API密钥步骤：</h4>
            <ol>
                <li>访问 <a href="https://open.weibo.com" target="_blank">新浪微博开放平台</a></li>
                <li>注册/登录开发者账号</li>
                <li>进入"应用管理"，创建一个新应用</li>
                <li>填写应用基本信息</li>
                <li>在应用详情页获取App Key和App Secret</li>
                <li>配置应用权限，申请"内容发布"权限</li>
                <li>完成开发者认证</li>
            </ol>
            <p><strong>注意：</strong>微博平台需要实名认证。</p>
        '
    ],
    'douban' => [
        'name' => '豆瓣',
        'guide' => '
            <h4>获取豆瓣开放平台API密钥步骤：</h4>
            <ol>
                <li>访问 <a href="https://developers.douban.com" target="_blank">豆瓣开发者平台</a></li>
                <li>注册/登录开发者账号</li>
                <li>创建一个新应用</li>
                <li>填写应用基本信息</li>
                <li>在应用详情页获取API Key和Secret</li>
                <li>配置应用权限</li>
            </ol>
            <p><strong>注意：</strong>豆瓣平台对内容质量和账号等级有一定要求。</p>
        '
    ],
    'kuaikan' => [
        'name' => '快看漫画',
        'guide' => '
            <h4>获取快看漫画开放平台API密钥步骤：</h4>
            <ol>
                <li>联系快看漫画商务合作团队</li>
                <li>提交开发者资质和应用信息</li>
                <li>等待平台审核</li>
                <li>审核通过后获取API密钥</li>
            </ol>
            <p><strong>注意：</strong>快看漫画平台主要面向合作方开放API。</p>
        '
    ],
    'yilan' => [
        'name' => '一览',
        'guide' => '
            <h4>获取一览开放平台API密钥步骤：</h4>
            <ol>
                <li>访问一览开放平台联系商务团队</li>
                <li>提交开发者资质和应用信息</li>
                <li>等待平台审核</li>
                <li>审核通过后获取API密钥</li>
            </ol>
            <p><strong>注意：</strong>一览平台需要商务合作资质。</p>
        '
    ],
    'dayu' => [
        'name' => '大鱼号',
        'guide' => '
            <h4>获取大鱼号API密钥步骤：</h4>
            <ol>
                <li>登录 <a href="https://dayu.uc.cn" target="_blank">大鱼号平台</a></li>
                <li>进入"创作工具"->"开放平台"</li>
                <li>申请成为开发者</li>
                <li>创建应用并获取API密钥</li>
            </ol>
            <p><strong>注意：</strong>需要完成大鱼号创作者认证。</p>
        '
    ],
    'chyxx' => [
        'name' => '创头条',
        'guide' => '
            <h4>获取创头条API密钥步骤：</h4>
            <ol>
                <li>联系创头条商务合作团队</li>
                <li>提交开发者资质和应用信息</li>
                <li>等待平台审核</li>
                <li>审核通过后获取API密钥</li>
            </ol>
            <p><strong>注意：</strong>创头条平台主要面向合作方开放API。</p>
        '
    ],
    'maoyan' => [
        'name' => '猫眼娱乐',
        'guide' => '
            <h4>获取猫眼娱乐开放平台API密钥步骤：</h4>
            <ol>
                <li>访问猫眼娱乐开放平台联系商务团队</li>
                <li>提交开发者资质和应用信息</li>
                <li>等待平台审核</li>
                <li>审核通过后获取API密钥</li>
            </ol>
            <p><strong>注意：</strong>猫眼娱乐平台主要面向合作方开放API。</p>
        '
    ],
    'alizhizhen' => [
        'name' => '阿里知站',
        'guide' => '
            <h4>获取阿里知站API密钥步骤：</h4>
            <ol>
                <li>登录阿里知站平台</li>
                <li>进入开发者中心</li>
                <li>申请成为开发者</li>
                <li>创建应用并获取API密钥</li>
            </ol>
            <p><strong>注意：</strong>需要完成阿里知站创作者认证。</p>
        '
    ],
    'pengpai' => [
        'name' => '澎湃新闻',
        'guide' => '
            <h4>获取澎湃新闻开放平台API密钥步骤：</h4>
            <ol>
                <li>联系澎湃新闻商务合作团队</li>
                <li>提交开发者资质和应用信息</li>
                <li>等待平台审核</li>
                <li>审核通过后获取API密钥</li>
            </ol>
            <p><strong>注意：</strong>澎湃新闻平台主要面向合作方开放API。</p>
        '
    ],
    'huxiu' => [
        'name' => '虎嗅网',
        'guide' => '
            <h4>获取虎嗅网开放平台API密钥步骤：</h4>
            <ol>
                <li>联系虎嗅网商务合作团队</li>
                <li>提交开发者资质和应用信息</li>
                <li>等待平台审核</li>
                <li>审核通过后获取API密钥</li>
            </ol>
            <p><strong>注意：</strong>虎嗅网平台主要面向合作方开放API。</p>
        '
    ],
    'iyiou' => [
        'name' => '亿欧网',
        'guide' => '
            <h4>获取亿欧网开放平台API密钥步骤：</h4>
            <ol>
                <li>联系亿欧网商务合作团队</li>
                <li>提交开发者资质和应用信息</li>
                <li>等待平台审核</li>
                <li>审核通过后获取API密钥</li>
            </ol>
            <p><strong>注意：</strong>亿欧网平台主要面向合作方开放API。</p>
        '
    ],
    'tmtpost' => [
        'name' => '钛媒体',
        'guide' => '
            <h4>获取钛媒体开放平台API密钥步骤：</h4>
            <ol>
                <li>联系钛媒体商务合作团队</li>
                <li>提交开发者资质和应用信息</li>
                <li>等待平台审核</li>
                <li>审核通过后获取API密钥</li>
            </ol>
            <p><strong>注意：</strong>钛媒体平台主要面向合作方开放API。</p>
        '
    ]
];
?>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>平台配置管理 - 高光视刻</title>
    <link rel="stylesheet" href="https://unpkg.com/layui@2.8.0/dist/css/layui.css">
    <link rel="stylesheet" href="../../assets/css/admin-optimized.css">
    <style>
        .platform-card {
            margin-bottom: 20px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        .platform-card:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        }
        .platform-header {
            padding: 15px 20px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .platform-body {
            padding: 20px;
            background-color: white;
        }
        .platform-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        .platform-status {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-enabled {
            background-color: #d4edda;
            color: #155724;
        }
        .status-disabled {
            background-color: #fff3cd;
            color: #856404;
        }
        .config-section {
            margin-bottom: 20px;
        }
        .config-section h4 {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
            color: #495057;
        }
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .form-col {
            flex: 1;
        }
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="layui-layout layui-layout-admin">
        <?php 
        $header_path = '../../includes/header.php';
        $sidebar_path = '../../includes/sidebar.php';
        if (file_exists($header_path)) {
            include $header_path;
        }
        if (file_exists($sidebar_path)) {
            include $sidebar_path;
        }
        ?>
        
        <div class="layui-body">
            <div class="layui-card">
                <div class="layui-card-header">
                    <h3>平台配置管理</h3>
                    <p class="text-muted">配置各大内容平台的API参数，启用后可实现一键发布功能</p>
                </div>
                <div class="layui-card-body">
                    
                    <!-- 消息提示区域 -->
                    <?php if (!empty($success_msg)): ?>
                        <div class="layui-alert layui-alert-success" style="margin-bottom: 20px;">
                            <i class="layui-icon layui-icon-ok-circle"></i>
                            <?php echo htmlspecialchars($success_msg); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_msg)): ?>
                        <div class="layui-alert layui-alert-error" style="margin-bottom: 20px;">
                            <i class="layui-icon layui-icon-error-circle"></i>
                            <?php echo htmlspecialchars($error_msg); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- 平台配置卡片列表 -->
                    <div class="platform-config-list">
                        <?php foreach ($platforms as $platform): ?>
                            <?php 
                            // 解析配置JSON
                            $config = !empty($platform['config']) ? json_decode($platform['config'], true) : [];
                            
                            // 获取平台特定字段
                            $specific_fields = $platform_specific_fields[$platform['platform_key']] ?? [];
                            ?>
                            <div class="platform-card">
                                <div class="platform-header">
                                    <div class="platform-title"><?php echo htmlspecialchars($platform['platform_name']); ?></div>
                                    <div class="platform-status <?php echo $platform['status'] ? 'status-enabled' : 'status-disabled'; ?>">
                                        <?php echo $platform['status'] ? '已启用' : '已禁用'; ?>
                                    </div>
                                </div>
                                
                                <div class="platform-body">
                                    <form class="layui-form" action="platform_config.php" method="post">
                                        <input type="hidden" name="platform_key" value="<?php echo htmlspecialchars($platform['platform_key']); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        
                                        <!-- 基础配置 -->
                                        <div class="config-section">
                                            <h4>基础配置</h4>
                                            
                                            <!-- 平台特定配置字段 -->
                                            <?php foreach ($specific_fields as $field): ?>
                                                <div class="layui-form-item">
                                                    <label class="layui-form-label"><?php echo htmlspecialchars($field['label']); ?><?php echo (!empty($field['required']) && $field['required']) ? ' <span style="color: #ff0000;">*</span>' : ''; ?></label>
                                                    <div class="layui-input-block">
                                                        <input type="<?php echo htmlspecialchars($field['type']); ?>" name="<?php echo htmlspecialchars($field['name']); ?>" 
                                                               value="<?php echo htmlspecialchars($platform[$field['name']] ?? ''); ?>" 
                                                               class="layui-input" 
                                                               placeholder="请输入<?php echo htmlspecialchars($field['label']); ?>">
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <!-- 发布设置 -->
                                        <div class="config-section">
                                            <h4>发布设置</h4>
                                            
                                            <div class="form-row">
                                                <div class="form-col">
                                                    <div class="layui-form-item">
                                                        <label class="layui-form-label">发布类型</label>
                                                        <div class="layui-input-block">
                                                            <select name="publish_type" class="layui-select">
                                                                <option value="auto" <?php echo (isset($config['publish_type']) && $config['publish_type'] === 'auto') ? 'selected' : ''; ?>>自动（根据内容类型）</option>
                                                                <option value="article" <?php echo (isset($config['publish_type']) && $config['publish_type'] === 'article') ? 'selected' : ''; ?>>文章</option>
                                                                <option value="video" <?php echo (isset($config['publish_type']) && $config['publish_type'] === 'video') ? 'selected' : ''; ?>>视频</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-col">
                                                    <div class="layui-form-item">
                                                        <label class="layui-form-label">启用状态</label>
                                                        <div class="layui-input-block">
                                                            <input type="checkbox" name="enabled" lay-skin="switch" 
                                                                   lay-text="启用|禁用" 
                                                                   <?php echo $platform['status'] ? 'checked' : ''; ?>>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="layui-form-item">
                                                <label class="layui-form-label">标题模板</label>
                                                <div class="layui-input-block">
                                                    <input type="text" name="title_template" 
                                                           value="<?php echo htmlspecialchars($config['title_template'] ?? ''); ?>" 
                                                           class="layui-input" 
                                                           placeholder="留空使用原始标题，支持变量如：{title}、{category}">
                                                </div>
                                            </div>
                                            
                                            <div class="layui-form-item">
                                                <label class="layui-form-label">内容模板</label>
                                                <div class="layui-input-block">
                                                    <textarea name="content_template" class="layui-textarea" 
                                                              placeholder="留空使用原始内容，支持变量如：{content}、{summary}" 
                                                              rows="3"><?php echo htmlspecialchars($config['content_template'] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- 操作按钮 -->
                                        <div class="layui-form-item">
                                            <div class="layui-input-block">
                                                <button class="layui-btn" lay-submit lay-filter="saveConfig">保存配置</button>
                                                <button type="button" class="layui-btn layui-btn-primary" onclick="resetForm(this)">重置</button>
                                                <button type="button" class="layui-btn layui-btn-warm" onclick="toggleApiGuide('<?php echo $platform['platform_key']; ?>')">获取API方法</button>
                                            </div>
                                        </div>
                                        
                                        <!-- API指南内容（默认隐藏） -->
                                        <div id="api-guide-<?php echo $platform['platform_key']; ?>" class="api-guide-content" style="display: none; margin-top: 20px; padding: 15px; border: 1px solid #e6e6e6; border-radius: 4px; background-color: #f9f9f9;">
                                            <h4><?php echo htmlspecialchars($platform['platform_name']); ?> - 获取API密钥方法</h4>
                                            <?php if (isset($platform_api_guides[$platform['platform_key']])): ?>
                                                <?php echo $platform_api_guides[$platform['platform_key']]['guide']; ?>
                                            <?php else: ?>
                                                <p>暂无该平台的API获取方法说明。</p>
                                            <?php endif; ?>
                                            <button type="button" class="layui-btn layui-btn-primary layui-btn-sm" onclick="toggleApiGuide('<?php echo $platform['platform_key']; ?>')" style="margin-top: 10px;">收起</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/layui@2.8.0/dist/layui.js"></script>
    <script>
    layui.use(['element', 'form', 'layer'], function(){
        var element = layui.element;
        var form = layui.form;
        var layer = layui.layer;
        
        // 初始化
        element.render();
        form.render();
        
        // 表单验证
        form.verify({
            required: function(value, item) {
                if (!value) {
                    return '该字段不能为空';
                }
            }
        });
        
        // 重置表单
        window.resetForm = function(btn) {
            var form = btn.closest('form');
            form.reset();
            form.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
                checkbox.checked = checkbox.defaultChecked;
            });
            form.querySelectorAll('input[type="radio"]').forEach(function(radio) {
                radio.checked = radio.defaultChecked;
            });
            form.querySelectorAll('select').forEach(function(select) {
                select.value = select.defaultValue;
            });
            form.querySelectorAll('.layui-form-switch').forEach(function(switchElem) {
                var input = switchElem.previousElementSibling;
                if (input && input.type === 'checkbox') {
                    input.checked = input.defaultChecked;
                }
            });
            layui.form.render();
        };
        
        // 切换API指南显示/隐藏
        window.toggleApiGuide = function(platformKey) {
            var guideElement = document.getElementById('api-guide-' + platformKey);
            if (guideElement) {
                if (guideElement.style.display === 'none') {
                    // 隐藏所有API指南
                    document.querySelectorAll('.api-guide-content').forEach(function(el) {
                        el.style.display = 'none';
                    });
                    // 显示当前平台的API指南
                    guideElement.style.display = 'block';
                } else {
                    // 隐藏当前平台的API指南
                    guideElement.style.display = 'none';
                }
            }
        };
    });
    </script>
    
    <style>
        .api-guide-content h4 {
            margin-top: 0;
            color: #333;
        }
        .api-guide-content ol {
            padding-left: 20px;
        }
        .api-guide-content li {
            margin-bottom: 8px;
        }
        .api-guide-content a {
            color: #1E9FFF;
            text-decoration: underline;
        }
        .api-guide-content p strong {
            color: #ff6600;
        }
    </style>
</body>
</html>