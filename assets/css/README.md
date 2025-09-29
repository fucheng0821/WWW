# CSS 文件结构说明

## 目录结构

```
assets/
├── css/
│   ├── base/
│   │   ├── reset.css          # CSS重置
│   │   ├── variables.css      # CSS变量
│   │   └── typography.css     # 排版样式
│   ├── components/
│   │   ├── buttons.css        # 按钮样式
│   │   ├── forms.css          # 表单样式
│   │   ├── cards.css          # 卡片样式
│   │   └── navigation.css     # 导航样式
│   ├── layout/
│   │   ├── grid.css           # 网格系统
│   │   ├── header.css         # 头部样式
│   │   └── footer.css         # 底部样式
│   ├── pages/
│   │   ├── frontend/
│   │   │   ├── home.css       # 首页样式
│   │   │   └── inner.css      # 内页通用样式
│   │   └── admin/
│   │       ├── admin.css      # 后端管理样式
│   │       └── dashboard.css  # 控制台样式
│   ├── utils/
│   │   ├── helpers.css        # 辅助类
│   │   └── animations.css     # 动画样式
│   ├── responsive.css         # 响应式样式
│   └── main.css               # 主样式文件(导入所有CSS)
```

## 使用说明

1. 在HTML文件中引入主样式文件：
   ```html
   <link rel="stylesheet" href="assets/css/main.css">
   ```

2. 根据需要引入特定页面的样式文件：
   ```html
   <link rel="stylesheet" href="assets/css/pages/frontend/home.css">
   ```

3. 后台页面引入后台样式：
   ```html
   <link rel="stylesheet" href="assets/css/pages/admin/admin.css">
   ```