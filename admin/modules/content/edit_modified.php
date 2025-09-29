});
            } catch (e) {
                console.error('视频上传功能初始化失�?', e);
                layui.layer.msg('视频上传功能初始化失败，请刷新页面重�?, {icon: 2});
            }
        }
        
        // 页面加载完成后初始化
        document.addEventListener('DOMContentLoaded', function() {
            
            // 初始化字体和标题选择器事�?            const fontSelect = document.getElementById('font-select');
            const headingSelect = document.getElementById('heading-select');
            
            if (fontSelect) {
                fontSelect.addEventListener('mousedown', function() {
                    window.saveEditorSelection();
                });
                
                fontSelect.addEventListener('change', function() {
});
            } catch (e) {
                console.error('视频上传功能初始化失�?', e);
                layui.layer.msg('视频上传功能初始化失败，请刷新页面重�?, {icon: 2});
            }
        }
        
        // 页面加载完成后初始化
        document.addEventListener('DOMContentLoaded', function() {
            
            // 初始化字体和标题选择器事�?            const fontSelect = document.getElementById('font-select');
            const headingSelect = document.getElementById('heading-select');
            
            if (fontSelect) {
                fontSelect.addEventListener('mousedown', function() {
                    window.saveEditorSelection();
                });
                
                fontSelect.addEventListener('change', function() {
