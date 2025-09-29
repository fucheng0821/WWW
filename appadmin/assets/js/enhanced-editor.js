/**
 * Enhanced Custom Editor Functions for Mobile
 * Provides advanced editing capabilities for the content management system
 */

// Enhanced editor functions
class EnhancedEditor {
    constructor(editorElement, textareaElement) {
        this.editor = editorElement;
        this.textarea = textareaElement;
        this.history = [];
        this.historyIndex = -1;
        this.maxHistory = 50;
        this.init();
    }

    init() {
        // Initialize event listeners
        this.editor.addEventListener('input', () => this.syncContent());
        this.editor.addEventListener('keyup', (e) => this.handleKeyCommands(e));
        this.editor.addEventListener('paste', (e) => this.handlePaste(e));
        
        // Initialize history
        this.saveHistory();
    }

    // Sync editor content with hidden textarea
    syncContent() {
        if (this.textarea) {
            this.textarea.value = this.editor.innerHTML;
        }
    }

    // Apply style to selection
    applyStyle(styleName, value) {
        try {
            // Try standard execCommand first
            document.execCommand(styleName === 'color' ? 'foreColor' : 'hiliteColor', false, value);
        } catch (e) {
            console.warn(`${styleName} command failed:`, e);
            // Fallback: wrap selection in span with style
            const selection = window.getSelection();
            if (selection.rangeCount > 0) {
                const range = selection.getRangeAt(0);
                const span = document.createElement('span');
                span.style[styleName] = value;
                if (range.collapsed) {
                    // If no text is selected, insert a zero-width space to apply style
                    span.innerHTML = '\u200B';
                    range.insertNode(span);
                    // Move cursor after the inserted span
                    range.setStartAfter(span);
                    range.collapse(true);
                    selection.removeAllRanges();
                    selection.addRange(range);
                } else {
                    // If text is selected, wrap it in the span
                    range.surroundContents(span);
                    // Select the wrapped text
                    range.selectNodeContents(span);
                    selection.removeAllRanges();
                    selection.addRange(range);
                }
            }
        }
    }

    // Save current state to history
    saveHistory() {
        const content = this.editor.innerHTML;
        // Only save if content has changed
        if (this.history.length === 0 || this.history[this.history.length - 1] !== content) {
            // Limit history size
            if (this.history.length >= this.maxHistory) {
                this.history.shift();
            }
            this.history.push(content);
            this.historyIndex = this.history.length - 1;
        }
    }

    // Undo last action
    undo() {
        if (this.historyIndex > 0) {
            this.historyIndex--;
            this.editor.innerHTML = this.history[this.historyIndex];
            this.syncContent();
        }
    }

    // Redo last undone action
    redo() {
        if (this.historyIndex < this.history.length - 1) {
            this.historyIndex++;
            this.editor.innerHTML = this.history[this.historyIndex];
            this.syncContent();
        }
    }

    // Format text with enhanced options
    formatText(command, value = null) {
        this.saveHistory();
        
        // 尝试恢复保存的选区
        const selectionRestored = window.restoreEditorSelection();
        
        // 获取当前选区
        const selection = window.getSelection();
        
        // 确保编辑器有焦点
        if (this.editor) {
            this.editor.focus();
        }
        
        // 特殊处理：确保在执行格式化命令前有选区
        if (selection.rangeCount === 0) {
            // 如果没有选区，创建一个光标位置的选区
            const range = document.createRange();
            range.selectNodeContents(this.editor);
            range.collapse(false); // 移动到末尾
            selection.removeAllRanges();
            selection.addRange(range);
        }
        
        if (command === 'formatBlock') {
            // Enhanced heading formatting with unique styles
            if (value) {
                try {
                    document.execCommand(command, false, value);
                } catch (e) {
                    console.warn('formatBlock command failed:', e);
                    // Fallback: manually wrap content
                    this.applyHeading(value);
                }
            }
        } else if (command === 'heading') {
            // Custom heading implementation
            this.applyHeading(value);
        } else if (command === 'findReplace') {
            // Find and replace functionality
            this.findAndReplace();
        } else if (command === 'viewSource') {
            // View source code functionality
            this.viewSource();
        } else if (['alignLeft', 'alignCenter', 'alignRight', 'alignJustify'].includes(command)) {
            // Paragraph alignment commands
            this.formatParagraph(command);
        } else if (command === 'fontName' && value) {
            // Custom font implementation for better cross-browser compatibility
            try {
                // Try standard execCommand first
                document.execCommand('fontName', false, value.split(',')[0]);
            } catch (e) {
                console.warn('fontName command failed:', e);
                // Fallback: wrap selection in span with font-family style
                if (selection.rangeCount > 0) {
                    const range = selection.getRangeAt(0);
                    const span = document.createElement('span');
                    span.style.fontFamily = value;
                    if (range.collapsed) {
                        // If no text is selected, insert a zero-width space to apply font
                        span.innerHTML = '\u200B';
                        range.insertNode(span);
                        // Move cursor after the inserted span
                        range.setStartAfter(span);
                        range.collapse(true);
                        selection.removeAllRanges();
                        selection.addRange(range);
                    } else {
                        // If text is selected, wrap it in the span
                        range.surroundContents(span);
                        // Select the wrapped text
                        range.selectNodeContents(span);
                        selection.removeAllRanges();
                        selection.addRange(range);
                    }
                }
            }
        } else if (command === 'foreColor' && value) {
            // Font color command
            this.applyStyle('color', value);
        } else if (command === 'hiliteColor' && value) {
            // Background color command
            this.applyStyle('backgroundColor', value);
        } else {
            // Try document.execCommand for other commands
            try {
                document.execCommand(command, false, value);
            } catch (e) {
                console.warn('Command not supported:', command, e);
            }
        }
        
        this.editor.focus();
        this.syncContent();
    }

    // Format paragraph alignment
    formatParagraph(alignment) {
        // Get the current selection
        var selection = window.getSelection();
        if (!selection.rangeCount) return;

        // Get the current range
        var range = selection.getRangeAt(0);
        var parentElement = range.commonAncestorContainer;
        
        // Find the nearest block-level element
        while (parentElement && parentElement.nodeType === 3) {
            parentElement = parentElement.parentNode;
        }

        // Create a block-level element if the selection is not in one
        if (!parentElement || ['P', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'DIV', 'LI'].indexOf(parentElement.tagName) === -1) {
            // Create a paragraph around the selection
            var p = document.createElement('p');
            range.surroundContents(p);
            parentElement = p;
        }

        // Remove any existing text-align styles
        parentElement.style.textAlign = '';
        
        // Apply the new alignment
        switch (alignment) {
            case 'alignLeft':
                parentElement.style.textAlign = 'left';
                break;
            case 'alignCenter':
                parentElement.style.textAlign = 'center';
                break;
            case 'alignRight':
                parentElement.style.textAlign = 'right';
                break;
            case 'alignJustify':
                parentElement.style.textAlign = 'justify';
                break;
        }
        
        // Save the new state
        this.syncContent();
    }

    // Apply custom heading styles
    applyHeading(level) {
        const selection = window.getSelection();
        if (!selection.rangeCount) return;

        const range = selection.getRangeAt(0);
        const selectedContent = range.extractContents();
        
        // Create heading element
        let heading;
        switch(level) {
            case 'h1':
                heading = document.createElement('h1');
                break;
            case 'h2':
                heading = document.createElement('h2');
                break;
            case 'h3':
                heading = document.createElement('h3');
                break;
            case 'h4':
                heading = document.createElement('h4');
                break;
            case 'h5':
                heading = document.createElement('h5');
                break;
            case 'h6':
                heading = document.createElement('h6');
                break;
            default:
                heading = document.createElement('p');
        }
        
        // Apply unique styles to each heading level
        this.applyHeadingStyles(heading, level);
        
        // Add selected content to heading
        heading.appendChild(selectedContent);
        
        // Insert heading
        range.insertNode(heading);
        
        // Move cursor after the heading
        const newRange = document.createRange();
        newRange.setStartAfter(heading);
        newRange.collapse(true);
        selection.removeAllRanges();
        selection.addRange(newRange);
    }

    // Apply unique styles to headings
    applyHeadingStyles(heading, level) {
        // Remove any existing heading classes
        heading.className = '';
        
        // Apply unique styles based on heading level
        switch(level) {
            case 'h1':
                heading.style.fontSize = '2em';
                heading.style.fontWeight = 'bold';
                heading.style.color = '#2c3e50';
                heading.style.margin = '0.67em 0';
                heading.style.borderBottom = '2px solid #3498db';
                heading.style.paddingBottom = '0.3em';
                break;
            case 'h2':
                heading.style.fontSize = '1.5em';
                heading.style.fontWeight = 'bold';
                heading.style.color = '#34495e';
                heading.style.margin = '0.83em 0';
                heading.style.borderLeft = '4px solid #2ecc71';
                heading.style.paddingLeft = '0.5em';
                break;
            case 'h3':
                heading.style.fontSize = '1.17em';
                heading.style.fontWeight = 'bold';
                heading.style.color = '#555';
                heading.style.margin = '1em 0';
                heading.style.backgroundColor = '#f8f9fa';
                heading.style.padding = '0.5em';
                heading.style.borderRadius = '4px';
                break;
            case 'h4':
                heading.style.fontSize = '1em';
                heading.style.fontWeight = 'bold';
                heading.style.color = '#666';
                heading.style.margin = '1.33em 0';
                heading.style.textTransform = 'uppercase';
                heading.style.letterSpacing = '1px';
                break;
            case 'h5':
                heading.style.fontSize = '0.83em';
                heading.style.fontWeight = 'bold';
                heading.style.color = '#777';
                heading.style.margin = '1.67em 0';
                heading.style.fontStyle = 'italic';
                break;
            case 'h6':
                heading.style.fontSize = '0.67em';
                heading.style.fontWeight = 'bold';
                heading.style.color = '#888';
                heading.style.margin = '2.33em 0';
                heading.style.textDecoration = 'underline';
                break;
        }
    }

    // Find and replace functionality
    findAndReplace() {
        layui.layer.open({
            type: 1,
            title: '查找和替换',
            area: ['90%', '300px'],
            content: `
                <div style="padding: 20px;">
                    <div class="layui-form">
                        <div class="layui-form-item">
                            <label class="layui-form-label">查找</label>
                            <div class="layui-input-block">
                                <input type="text" id="findText" placeholder="输入要查找的文本" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">替换为</label>
                            <div class="layui-input-block">
                                <input type="text" id="replaceText" placeholder="输入替换文本" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <input type="checkbox" id="matchCase" title="区分大小写" lay-skin="primary">
                                <input type="checkbox" id="wholeWord" title="全字匹配" lay-skin="primary">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <button type="button" class="layui-btn" id="findBtn">查找</button>
                                <button type="button" class="layui-btn layui-btn-normal" id="replaceAllBtn">全部替换</button>
                                <button type="button" class="layui-btn layui-btn-primary" onclick="layui.layer.closeAll()">取消</button>
                            </div>
                        </div>
                    </div>
                </div>
            `,
            success: (layero, index) => {
                // Find button event
                document.getElementById('findBtn').onclick = () => {
                    const findText = document.getElementById('findText').value;
                    const matchCase = document.getElementById('matchCase').checked;
                    const wholeWord = document.getElementById('wholeWord').checked;
                    
                    if (findText) {
                        this.highlightText(findText, matchCase, wholeWord);
                        layui.layer.msg('查找完成');
                    } else {
                        layui.layer.msg('请输入要查找的文本');
                    }
                };
                
                // Replace all button event
                document.getElementById('replaceAllBtn').onclick = () => {
                    const findText = document.getElementById('findText').value;
                    const replaceText = document.getElementById('replaceText').value;
                    const matchCase = document.getElementById('matchCase').checked;
                    const wholeWord = document.getElementById('wholeWord').checked;
                    
                    if (findText) {
                        this.replaceAllText(findText, replaceText, matchCase, wholeWord);
                        layui.layer.msg('替换完成');
                    } else {
                        layui.layer.msg('请输入要查找的文本');
                    }
                };
            }
        });
    }

    // Highlight text in editor
    highlightText(findText, matchCase, wholeWord) {
        // Remove existing highlights
        this.removeHighlights();
        
        if (!findText) return;
        
        const content = this.editor.innerHTML;
        let regexFlags = 'g';
        if (!matchCase) {
            regexFlags += 'i';
        }
        
        let regex;
        if (wholeWord) {
            regex = new RegExp('\\b' + this.escapeRegExp(findText) + '\\b', regexFlags);
        } else {
            regex = new RegExp(this.escapeRegExp(findText), regexFlags);
        }
        
        const highlightedContent = content.replace(regex, (match) => {
            return `<span class="highlight-search" style="background-color: yellow; color: black;">${match}</span>`;
        });
        
        this.editor.innerHTML = highlightedContent;
        this.syncContent();
    }

    // Replace all occurrences of text
    replaceAllText(findText, replaceText, matchCase, wholeWord) {
        this.saveHistory();
        
        if (!findText) return;
        
        const content = this.editor.innerHTML;
        let regexFlags = 'g';
        if (!matchCase) {
            regexFlags += 'i';
        }
        
        let regex;
        if (wholeWord) {
            regex = new RegExp('\\b' + this.escapeRegExp(findText) + '\\b', regexFlags);
        } else {
            regex = new RegExp(this.escapeRegExp(findText), regexFlags);
        }
        
        const newContent = content.replace(regex, replaceText);
        this.editor.innerHTML = newContent;
        this.syncContent();
        
        // Remove highlights
        this.removeHighlights();
    }

    // Remove highlights
    removeHighlights() {
        const highlights = this.editor.querySelectorAll('.highlight-search');
        highlights.forEach(span => {
            const parent = span.parentNode;
            parent.replaceChild(document.createTextNode(span.textContent), span);
            parent.normalize();
        });
        this.syncContent();
    }

    // View source code functionality
    viewSource() {
        console.log('viewSource called');
        const htmlContent = this.editor.innerHTML;
        
        layui.layer.open({
            type: 1,
            title: '查看源代码',
            area: ['90%', '500px'],
            content: `
                <div style="padding: 20px;">
                    <div class="layui-form">
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <textarea id="sourceCode" class="layui-textarea" style="min-height: 300px; font-family: monospace; font-size: 12px;">${htmlContent}</textarea>
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <button type="button" class="layui-btn layui-btn-normal" id="applySourceBtn">应用</button>
                                <button type="button" class="layui-btn layui-btn-primary" onclick="layui.layer.closeAll()">取消</button>
                            </div>
                        </div>
                    </div>
                </div>
            `,
            success: (layero, index) => {
                console.log('viewSource dialog opened');
                // Apply button event
                document.getElementById('applySourceBtn').onclick = () => {
                    const sourceCode = document.getElementById('sourceCode').value;
                    this.saveHistory();
                    this.editor.innerHTML = sourceCode;
                    this.syncContent();
                    layui.layer.close(index);
                    layui.layer.msg('源代码已应用');
                };
            }
        });
    }
    
    // Escape special regex characters
    escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
    
    // Handle key commands
    handleKeyCommands(e) {
        // Ctrl+Z for undo
        if (e.ctrlKey && e.key === 'z') {
            e.preventDefault();
            this.undo();
        }
        // Ctrl+Y for redo
        else if (e.ctrlKey && e.key === 'y') {
            e.preventDefault();
            this.redo();
        }
        // Ctrl+F for find
        else if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            this.formatText('findReplace');
        }
    }
    
    // Handle paste events with enhanced formatting
    handlePaste(e) {
        this.saveHistory();
        
        // Get pasted data via clipboard API
        if (e.clipboardData && e.clipboardData.getData) {
            const plaintext = e.clipboardData.getData('text/plain');
            const htmltext = e.clipboardData.getData('text/html');
            
            // If we have HTML content, process it
            if (htmltext) {
                e.preventDefault();
                this.insertCleanHTML(htmltext);
            }
        }
    }
    
    // Insert clean HTML with formatting preserved
    insertCleanHTML(html) {
        // Create temporary div to parse HTML
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        
        // Clean the HTML content
        this.cleanHTMLElement(tempDiv);
        
        // Insert cleaned content
        const selection = window.getSelection();
        if (selection.rangeCount) {
            const range = selection.getRangeAt(0);
            range.deleteContents();
            range.insertNode(tempDiv);
            range.collapse(false);
        }
        
        this.syncContent();
    }
    
    // Clean HTML element and its children
    cleanHTMLElement(element) {
        // Remove unwanted attributes
        const allowedAttributes = ['src', 'href', 'alt', 'title', 'class', 'style'];
        const allElements = element.querySelectorAll('*');
        
        // Process the element itself
        this.cleanElementAttributes(element, allowedAttributes);
        
        // Process all child elements
        allElements.forEach(child => {
            this.cleanElementAttributes(child, allowedAttributes);
        });
    }
    
    // Clean element attributes
    cleanElementAttributes(element, allowedAttributes) {
        const attributes = Array.from(element.attributes);
        attributes.forEach(attr => {
            if (!allowedAttributes.includes(attr.name)) {
                element.removeAttribute(attr.name);
            }
        });
    }
    
    // Insert link
    insertLink() {
        layui.layer.prompt({
            formType: 2,
            title: '输入链接地址',
            value: 'https://',
            area: ['90%', '130px']
        }, (value, index, elem) => {
            if (value) {
                this.saveHistory();
                document.execCommand('createLink', false, value);
                this.syncContent();
            }
            layui.layer.close(index);
        });
    }
    
    // Insert image
    insertImage() {
        // 调用增强图片上传器
        if (window.imageUploader) {
            window.imageUploader.showUploadDialog();
        } else {
            layui.layer.msg('图片上传功能未初始化');
        }
    }
    
    // Insert video
    insertVideo() {
        layui.layer.open({
            type: 1,
            title: '插入视频',
            area: ['90%', '250px'],
            content: `
                <div style="padding: 20px;">
                    <div class="layui-form">
                        <div class="layui-form-item">
                            <label class="layui-form-label">视频URL</label>
                            <div class="layui-input-block">
                                <input type="text" id="videoUrl" placeholder="https://" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">宽度</label>
                            <div class="layui-input-inline">
                                <input type="number" id="videoWidth" value="640" class="layui-input">
                            </div>
                            <label class="layui-form-label">高度</label>
                            <div class="layui-input-inline">
                                <input type="number" id="videoHeight" value="360" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <button type="button" class="layui-btn layui-btn-primary" onclick="document.getElementById('videoUpload').click()">上传视频</button>
                                <input type="file" id="videoUpload" accept="video/*" style="display: none;">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <button type="button" class="layui-btn layui-btn-normal" id="confirmVideoBtn">确认</button>
                                <button type="button" class="layui-btn layui-btn-primary" onclick="layui.layer.closeAll()">取消</button>
                            </div>
                        </div>
                    </div>
                </div>
            `,
            success: (layero, index) => {
                // Upload button event
                document.getElementById('videoUpload').onchange = (e) => {
                    const file = e.target.files[0];
                    if (file) {
                        // Here you would typically upload the file to your server
                        // For this example, we'll just use a placeholder
                        layui.layer.msg('视频上传功能需要服务器端支持');
                    }
                };
                
                // Confirm button event
                document.getElementById('confirmVideoBtn').onclick = () => {
                    const videoUrl = document.getElementById('videoUrl').value;
                    const width = document.getElementById('videoWidth').value;
                    const height = document.getElementById('videoHeight').value;
                    
                    if (videoUrl) {
                        this.insertMediaEmbed(videoUrl, width, height);
                        layui.layer.close(index);
                    } else {
                        layui.layer.msg('请输入视频URL');
                    }
                };
            }
        });
    }
    
    // Insert table
    insertTable() {
        layui.layer.open({
            type: 1,
            title: '插入表格',
            area: ['90%', '200px'],
            content: `
                <div style="padding: 20px;">
                    <div class="layui-form">
                        <div class="layui-form-item">
                            <label class="layui-form-label">行数</label>
                            <div class="layui-input-inline">
                                <input type="number" id="tableRows" value="3" min="1" max="20" class="layui-input">
                            </div>
                            <label class="layui-form-label">列数</label>
                            <div class="layui-input-inline">
                                <input type="number" id="tableCols" value="3" min="1" max="10" class="layui-input">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">标题行</label>
                            <div class="layui-input-inline">
                                <input type="checkbox" id="tableHeader" title="包含标题行" lay-skin="primary">
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <button type="button" class="layui-btn" id="confirmTableBtn">确认</button>
                                <button type="button" class="layui-btn layui-btn-primary" onclick="layui.layer.closeAll()">取消</button>
                            </div>
                        </div>
                    </div>
                </div>
            `,
            success: (layero, index) => {
                document.getElementById('confirmTableBtn').onclick = () => {
                    const rows = parseInt(document.getElementById('tableRows').value) || 3;
                    const cols = parseInt(document.getElementById('tableCols').value) || 3;
                    const hasHeader = document.getElementById('tableHeader').checked;
                    
                    this.createTable(rows, cols, hasHeader);
                    layui.layer.close(index);
                };
            }
        });
    }

    // Insert media embed code
    insertMediaEmbed(url, width, height) {
        this.saveHistory();
        
        let embedHtml = '';
        
        // Check if it's an image
        if (/(\.jpg|\.jpeg|\.png|\.gif|\.webp)$/i.test(url)) {
            embedHtml = `<img src="${url}" width="${width}" height="${height}" style="max-width: 100%; height: auto; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">`;
        }
        // Check if it's a video file
        else if (/(\.mp4|\.webm|\.ogg|\.avi|\.mov|\.wmv|\.flv|\.mkv)$/i.test(url)) {
            embedHtml = `
                <video width="${width}" height="${height}" controls style="max-width: 100%; height: auto; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <source src="${url}" type="video/mp4">
                    您的浏览器不支持HTML5视频播放。
                </video>
            `;
        }
        // Assume it's an external embed (YouTube, Vimeo, etc.)
        else {
            embedHtml = `
                <iframe width="${width}" height="${height}" src="${url}" frameborder="0" allowfullscreen style="border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></iframe>
            `;
        }
        
        // Insert at cursor position
        const selection = window.getSelection();
        if (selection.rangeCount) {
            const range = selection.getRangeAt(0);
            range.deleteContents();
            
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = embedHtml;
            const fragment = document.createDocumentFragment();
            while (tempDiv.firstChild) {
                fragment.appendChild(tempDiv.firstChild);
            }
            
            range.insertNode(fragment);
            range.collapse(false);
        }
        
        this.syncContent();
    }
    
    // Create table
    createTable(rows, cols, hasHeader) {
        this.saveHistory();
        
        // Create table element
        const table = document.createElement('table');
        table.style.borderCollapse = 'collapse';
        table.style.width = '100%';
        table.style.margin = '10px 0';
        
        // Create table rows and cells
        for (let i = 0; i < rows; i++) {
            const tr = document.createElement('tr');
            
            for (let j = 0; j < cols; j++) {
                let cell;
                if (hasHeader && i === 0) {
                    cell = document.createElement('th');
                    cell.textContent = `标题 ${j + 1}`;
                } else {
                    cell = document.createElement('td');
                    cell.textContent = `单元格 ${i + 1}-${j + 1}`;
                }
                cell.style.border = '1px solid #ddd';
                cell.style.padding = '8px';
                cell.style.textAlign = 'left';
                tr.appendChild(cell);
            }
            
            table.appendChild(tr);
        }
        
        // Insert table at cursor position
        const selection = window.getSelection();
        if (selection.rangeCount) {
            const range = selection.getRangeAt(0);
            range.deleteContents();
            range.insertNode(table);
            range.collapse(false);
        }
        
        this.syncContent();
    }
}

// 全局变量用于在点击下拉菜单时保存编辑器选区
window.editorSelection = null;
window.editorSelectionEditor = null;

// 全局函数：保存编辑器选区状态
window.saveEditorSelection = function(editorElement) {
    try {
        const selection = window.getSelection();
        if (selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            
            // 检查选区是否在我们的编辑器内
            if (editorElement) {
                if (editorElement.contains(range.commonAncestorContainer)) {
                    window.editorSelection = range.cloneRange();
                    window.editorSelectionEditor = editorElement;
                    return true;
                }
            } else {
                // 如果没有指定编辑器，检查是否在任何contenteditable元素内
                let container = range.commonAncestorContainer;
                while (container && container.nodeType !== 1) {
                    container = container.parentNode;
                }
                
                if (container && container.hasAttribute && container.hasAttribute('contenteditable')) {
                    window.editorSelection = range.cloneRange();
                    window.editorSelectionEditor = container;
                    return true;
                }
            }
        }
    } catch (e) {
        console.warn('Failed to save selection:', e);
    }
    return false;
};

// 全局函数：恢复编辑器选区状态
window.restoreEditorSelection = function() {
    if (window.editorSelection && window.editorSelectionEditor) {
        try {
            // 确保编辑器元素仍然存在且可编辑
            if (document.contains(window.editorSelectionEditor) && 
                window.editorSelectionEditor.hasAttribute('contenteditable')) {
                
                // 确保编辑器有焦点
                window.editorSelectionEditor.focus();
                
                const selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(window.editorSelection);
                
                // 验证选区是否成功设置
                if (selection.rangeCount > 0) {
                    const range = selection.getRangeAt(0);
                    if (window.editorSelectionEditor.contains(range.commonAncestorContainer)) {
                        return true;
                    }
                }
            }
        } catch (e) {
            console.warn('Failed to restore selection:', e);
        } finally {
            // 清除保存的选区，避免重复使用
            window.editorSelection = null;
            window.editorSelectionEditor = null;
        }
    }
    return false;
};

// 重写全局formatText函数，确保正确使用保存的选区
const originalFormatText = window.formatText;
window.formatText = function(command, value = null) {
    if (window.enhancedEditor) {
        // 尝试恢复保存的选区
        const selectionRestored = window.restoreEditorSelection();
        
        // 如果没有保存的选区，则尝试获取当前选区
        if (!selectionRestored) {
            try {
                const selection = window.getSelection();
                if (selection.rangeCount === 0) {
                    // 如果没有选区，将光标移到编辑器末尾
                    const range = document.createRange();
                    range.selectNodeContents(window.enhancedEditor.editor);
                    range.collapse(false); // 移到末尾
                    selection.removeAllRanges();
                    selection.addRange(range);
                }
            } catch (e) {
                console.warn('Failed to set cursor position:', e);
            }
        }
        
        // 调用原始的formatText方法
        window.enhancedEditor.formatText(command, value);
    }
};

// Initialize enhanced editor when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Make EnhancedEditor globally available
    window.EnhancedEditor = EnhancedEditor;
    
    // Initialize editor instance if needed
    const editorElement = document.getElementById('custom-editor');
    const textareaElement = document.getElementById('content-input');
    if (editorElement && textareaElement) {
        window.enhancedEditor = new EnhancedEditor(editorElement, textareaElement);
        // Set up global formatText function if not already set
        if (typeof window.formatText !== 'function') {
            window.formatText = function(command, value = null) {
                if (window.enhancedEditor) {
                    window.enhancedEditor.formatText(command, value);
                }
            };
        }
    }
});