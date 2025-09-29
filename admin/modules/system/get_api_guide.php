<?php
/**
 * 获取平台API指南
 */

// 设置响应头
header('Content-Type: text/html; charset=utf-8');

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

// 获取平台参数
$platform = $_GET['platform'] ?? '';

// 检查平台是否存在
if (isset($platform_api_guides[$platform])) {
    echo $platform_api_guides[$platform]['guide'];
} else {
    echo '<p>未找到指定平台的API获取方法。</p>';
}
?>