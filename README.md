# 社区便民留言板

基于 PHP 原生开发的社区便民留言板网站，支持居民求助、意见建议、失物招领等功能。

## 功能特性

- **首页展示**：滚动显示最新留言信息，统计各类留言数量
- **留言发布**：用户可提交留言，选择类型（求助/建议/失物招领），支持图片上传
- **排序筛选**：支持按时间/热度排序，按类型筛选
- **后台管理**：管理员可审核、通过、拒绝、删除留言
- **响应式布局**：适配手机和电脑端

## 技术栈

- **后端**：PHP 原生开发
- **数据库**：MySQL
- **前端**：HTML5 + CSS3 + JavaScript（原生）
- **特性**：响应式设计、图片上传、分页、搜索

## 安装部署

### 环境要求

- PHP >= 7.4
- MySQL >= 5.7
- Apache / Nginx

### 安装步骤

1. 将项目文件上传到 Web 服务器根目录

2. 配置数据库连接（二选一）：
   - 编辑 `config/database.php`；或
   - 通过环境变量 `DB_HOST` / `DB_USER` / `DB_PASS` / `DB_NAME` 注入（流水线、容器部署推荐）

3. 执行安装（两种方式效果一致，均可重复执行）：
   - Web 方式，访问安装脚本：
     ```
     http://your-domain/install.php
     ```
   - 命令行方式（构建、上线、流水线、部署、本地开发推荐）：
     ```bash
     php cli_install.php
     ```

4. 安装完成后删除 `install.php` 文件

5. 访问首页：
   ```
   http://your-domain/index.php
   ```

6. 访问后台：
   ```
   http://your-domain/admin/login.php
   ```

### 旧版本升级

安装与迁移共用同一份结构定义 `database/schema.sql`，升级旧库无需单独的迁移脚本：

- 命令行（幂等，可重复执行）：
  ```bash
  php cli_install.php
  ```
- 或手动执行：
  ```bash
  mysql -u root -p community_board < database/schema.sql
  ```

升级只补齐缺失的表结构，已有留言、收藏、举报数据及管理员账号均不受影响；默认管理员仅在不存在时创建，示例数据仅在留言表为空时插入。

### 默认管理员账号

- 用户名：`admin`
- 密码：`admin123`

## 项目结构

```
label-9900013/
├── index.php              # 首页
├── submit.php             # 发布留言页
├── detail.php             # 留言详情页
├── favorites.php          # 我的收藏页
├── install.php            # Web 安装脚本（与 CLI 共用同一套逻辑）
├── cli_install.php        # 命令行安装/迁移脚本（构建、流水线、部署、本地开发通用）
├── config/
│   └── database.php       # 数据库配置（支持环境变量覆盖）
├── database/
│   └── schema.sql         # 数据库结构定义（唯一数据源，幂等）
├── includes/
│   ├── functions.php      # 公共函数
│   ├── migration.php      # 安装/迁移模块（install.php 与 cli_install.php 共用）
│   ├── header.php         # 前台头部
│   └── footer.php         # 前台底部
├── api/
│   ├── submit.php         # 留言提交API
│   ├── favorite.php       # 收藏API
│   └── report.php         # 举报API
├── admin/
│   ├── index.php          # 后台管理页
│   ├── login.php          # 后台登录
│   ├── api.php            # 后台API
│   ├── reports.php        # 举报管理页
│   ├── logout.php         # 退出登录
│   └── header.php         # 后台头部
├── assets/
│   ├── css/
│   │   └── style.css      # 样式文件
│   └── js/
│       └── main.js        # 脚本文件
└── uploads/               # 图片上传目录
```

## 数据库配置

编辑 `config/database.php` 文件，或通过同名环境变量覆盖（默认值不变）：

```php
define('DB_HOST', dbConfigEnv('DB_HOST', 'localhost'));
define('DB_USER', dbConfigEnv('DB_USER', 'root'));
define('DB_PASS', dbConfigEnv('DB_PASS', '123456'));
define('DB_NAME', dbConfigEnv('DB_NAME', 'community_board'));
```

表结构变更只需修改 `database/schema.sql`，所有安装/迁移入口会自动生效。

## 使用说明

### 前台功能

1. **浏览留言**：首页展示所有已审核通过的留言
2. **筛选类型**：点击类型标签筛选特定类型的留言
3. **排序方式**：支持按时间或热度排序
4. **发布留言**：点击"发布留言"进入提交页面
5. **查看详情**：点击留言卡片查看完整内容

### 后台管理

1. 登录后台管理系统
2. 查看所有留言（支持状态、类型筛选和关键词搜索）
3. 审核留言（通过/拒绝）
4. 删除不当留言
5. 查看留言详情

## 注意事项

- 安装完成后务必删除 `install.php`
- 修改默认管理员密码
- 确保 `uploads/` 目录有写入权限
- 建议配置 HTTPS 保障数据传输安全
