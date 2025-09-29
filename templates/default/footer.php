<!-- 询价模块 -->
<!--
<section class="inquiry-section">
    <div class="container">
        <h2>需要专业创意服务？</h2>
        <p>立即联系我们的专业顾问，获取免费咨询和报价</p>
        <\!-- 修改询价模块，添加输入框形式 -\->
        <div class="inquiry-form-container">
            <form id="footer-inquiry-form" class="footer-inquiry-form" method="POST">
                <div class="form-group">
                    <input type="text" name="name" placeholder="姓名 *" required>
                    <input type="text" name="phone" placeholder="电话 *" required>
                </div>
                <div class="form-group">
                    <input type="text" name="service_type" placeholder="服务类型 *" required>
                    <select name="budget" class="budget-select">
                        <option value="">预算范围</option>
                        <option value="1万以下">1万以下</option>
                        <option value="1-5万">1-5万</option>
                        <option value="5-10万">5-10万</option>
                        <option value="10-20万">10-20万</option>
                        <option value="20万以上">20万以上</option>
                    </select>
                </div>
                <div class="form-group">
                    <textarea name="message" placeholder="详细需求 *" rows="3" required></textarea>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-comment"></i> 立即询价
                </button>
            </form>
        </div>
    </div>
</section>
-->

<footer class="site-footer">
    <div class="container">
        <div class="footer-content">
            <!-- 将四个栏目放在一行显示 -->
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md3 layui-col-sm6">
                    <div class="footer-section">
                        <h3>关于我们</h3>
                        <p><?php echo get_config('company_name', '合肥沃蓝品牌设计有限公司'); ?></p>
                        <p>专业提供视频制作、平面设计、网站建设、商业摄影、活动策划等创意服务。</p>
                    </div>
                </div>
                
                <div class="layui-col-md3 layui-col-sm6">
                    <div class="footer-section">
                        <h3>服务项目</h3>
                        <ul class="footer-links">
                            <?php
                            $service_categories = get_categories(0, true);
                            foreach ($service_categories as $category):
                                if ($category['slug'] != 'contact'):
                            ?>
                            <li><a href="<?php echo url($category['slug'] . '/'); ?>"><?php echo $category['name']; ?></a></li>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </ul>
                    </div>
                </div>
                
                <div class="layui-col-md3 layui-col-sm6">
                    <div class="footer-section">
                        <h3>快速链接</h3>
                        <ul class="footer-links">
                            <li><a href="<?php echo url(); ?>">首页</a></li>
                            <li><a href="<?php echo url('contact/'); ?>">联系我们</a></li>
                            <li><a href="<?php echo url('contact/'); ?>">在线询价</a></li>
                            <li><a href="<?php echo url('contact/faq/'); ?>">常见问题</a></li>

                        </ul>
                    </div>
                </div>
                
                <div class="layui-col-md3 layui-col-sm6">
                    <div class="footer-section">
                        <h3>联系方式</h3>
                        <ul class="footer-links">
                            <li><a href="javascript:;"><i class="fas fa-building" style="margin-right: 10px;"></i><?php echo get_config('company_name', '合肥沃蓝品牌设计有限公司'); ?></a></li>
                            <li><a href="tel:<?php echo get_config('contact_phone', '400-888-8888'); ?>"><i class="fas fa-phone" style="margin-right: 10px;"></i><?php echo get_config('contact_phone', '400-888-8888'); ?></a></li>
                            <li><a href="mailto:<?php echo get_config('contact_email', 'info@gaoguangshike.cn'); ?>"><i class="fas fa-envelope" style="margin-right: 10px;"></i><?php echo get_config('contact_email', 'info@gaoguangshike.cn'); ?></a></li>
                            <li><a href="javascript:;"><i class="fas fa-map-marker-alt" style="margin-right: 10px;"></i><?php echo get_config('contact_address', '北京市朝阳区创意园区'); ?></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="layui-row">
                <div class="layui-col-md6">
                    <p>&copy; 2024 <?php echo get_config('company_name', '合肥沃蓝品牌设计有限公司'); ?> 版权所有</p>
                </div>
                <div class="layui-col-md6" style="text-align: right;">
                    <p>
                        <a href="#" style="color: #999;">隐私政策</a> | 
                        <a href="#" style="color: #999;">服务条款</a> | 
                        <a href="#" style="color: #999;">网站地图</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- 返回顶部 -->
<div class="back-to-top" id="backToTop">
    <i class="fas fa-chevron-up"></i>
</div>

<!-- 在线客服浮动按钮 -->
<div class="floating-contact">
    <div class="floating-btn floating-inquiry">
        <a href="<?php echo url('contact/'); ?>">
            <i class="fas fa-comment-dots"></i>
            <span>咨询</span>
        </a>
    </div>
    <div class="floating-btn floating-phone">
        <a href="tel:<?php echo get_config('contact_phone', '400-888-8888'); ?>">
            <i class="fas fa-phone"></i>
            <span>电话</span>
        </a>
    </div>
    <div class="floating-btn floating-wechat">
        <a href="javascript:;">
            <i class="fab fa-weixin"></i>
            <span>微信</span>
        </a>
        <div class="wechat-qr">
            <img src="<?php echo get_config('wechat_qr', '/templates/default/assets/images/wechat-qr.jpg'); ?>" alt="微信二维码">
            <p>扫码添加微信</p>
        </div>
    </div>
</div>

<!-- 页脚询价表单处理脚本 (当前表单被注释掉，此脚本已禁用) -->
<!--
<script>
// 处理页脚询价表单提交
// document.getElementById('footer-inquiry-form').addEventListener('submit', function(e) {
//     e.preventDefault();
//     
//     // 显示提交中状态
//     var submitBtn = this.querySelector('button[type="submit"]');
//     var originalText = submitBtn.innerHTML;
//     submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 提交中...';
//     submitBtn.disabled = true;
//     
//     var formData = new FormData(this);
//     var data = {
//         name: formData.get('name'),
//         phone: formData.get('phone'),
//         service_type: formData.get('service_type'),
//         project_budget: formData.get('budget'),
//         message: '来自首页底部询价模块',
//         email: '',
//         company: '',
//         project_deadline: ''
//     };
//     
//     // 发送询价请求
//     fetch('api/inquiry.php', {
//         method: 'POST',
//         headers: {
//             'Content-Type': 'application/json',
//         },
//         body: JSON.stringify(data)
//     })
//     .then(response => {
//         if (!response.ok) {
//             throw new Error('网络响应错误: ' + response.status);
//         }
//         return response.json();
//     })
//     .then(result => {
//         if (result.success) {
//             alert('询价提交成功，我们会尽快与您联系！');
//             this.reset();
//         } else {
//             alert('提交失败：' + (result.message || '未知错误'));
//         }
//     })
//     .catch(error => {
//         console.error('提交错误:', error);
//         alert('网络错误，请稍后重试\n错误信息: ' + error.message);
//     })
//     .finally(() => {
//         // 恢复按钮状态
//         submitBtn.innerHTML = originalText;
//         submitBtn.disabled = false;
//     });
// });
</script>
-->