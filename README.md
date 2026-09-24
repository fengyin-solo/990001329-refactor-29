# 社区便民留言板

基于 PHP 原生开发的社区便民留言板网站，支持居民求助、意见建议、失物招领等功能。

## 功能特性

- **首页展示**：滚动显示最新留言信息，统计各类留言数量
- **留言发布**：用户可提交留言，选择类型（求助/建议/失物招领），支持图片上传
- **排序筛选**：支持按时间/热度排序，按类型筛选
- **后台管理**：管理员可审核、通过、拒绝、删除留言
- **收藏与举报**：访客可收藏留言、举报不当内容，后台统一处理举报
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

### 本地 / Web 安装

1. 将项目文件上传到 Web 服务器根目录。
2. 按需修改 `config/database.php`，或设置 `DB_HOST`、`DB_USER`、`DB_PASS`、`DB_NAME`、`DB_CHARSET` 环境变量；未设置时沿用配置文件。
3. 访问安装脚本创建/升级数据库：
   ```
   http://your-domain/install.php
   ```
   Web 安装会执行结构迁移、确保默认管理员，并仅在留言表为空时导入示例留言。安装脚本可重复执行，不会清空或复制业务数据。
4. 安装完成后删除或禁止公网访问 `install.php`。
5. 访问首页和后台：
   ```
   http://your-domain/index.php
   http://your-domain/admin/login.php
   ```

### 命令行 / 构建流水线 / 部署

所有环境使用同一个幂等入口：

```bash
# 新安装和旧库升级相同：创建缺失的库、表，并记录已执行迁移
php database/migrate.php

# 结构 + 默认管理员（生产部署推荐）
php cli_install.php

# 本地演示环境需要示例数据时使用；已有留言的库不会重复导入
php cli_install.php --with-samples
```

历史命令仍然可用，`database/migration_add_favorites.sql` 和 `database/migration_add_reports.sql` 是统一迁移文件的兼容入口：

```bash
mysql -u root -p community_board < database/migration_add_favorites.sql
mysql -u root -p community_board < database/migration_add_reports.sql
```

### 默认管理员

仅当不存在 `admin` 账号时创建；已存在则保持账号及密码不变：

- 用户名：`admin`
- 初始密码：`admin123`

已有管理员账号和已修改的密码不会被迁移或安装过程覆盖。

## 项目结构

```
label-9900013/
├── index.php              # 首页
├── submit.php             # 发布留言页
├── detail.php             # 留言详情页
├── cli_install.php          # 命令行安装/升级入口
├── install.php              # Web 安装/升级入口
├── config/
│   └── database.php         # 数据库配置
├── database/
│   ├── migrate.php          # 统一结构迁移入口
│   ├── setup.php            # 迁移和种子编排入口
│   ├── seed_admin.php       # 默认管理员种子
│   ├── seed_samples.php     # 示例留言种子
│   └── migrations/          # 唯一结构变更来源（按编号递增）
├── includes/
│   ├── functions.php      # 公共函数
│   ├── header.php         # 前台头部
│   └── footer.php         # 前台底部
├── api/
│   └── submit.php         # 留言提交API
├── admin/
│   ├── index.php          # 后台管理页
│   ├── login.php          # 后台登录
│   ├── api.php            # 后台API
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

编辑 `config/database.php`，或在部署环境中配置同名环境变量：

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '123456');
define('DB_NAME', 'community_board');
define('DB_CHARSET', 'utf8mb4');
```

## 数据库变更规范

- 表结构只在 `database/migrations/` 中新增按编号排序的 `.sql` 文件，不再把建表语句复制到安装脚本。
- 每个新迁移必须可重复执行，例如使用 `CREATE TABLE IF NOT EXISTS`；涉及旧 MySQL 的列/索引变更需用存在性检查保护。
- 默认管理员只维护在 `database/seed_admin.php`，示例留言只维护在 `database/seed_samples.php`。
- 新安装、旧库升级和重复执行都运行 `php database/migrate.php`，结构以迁移记录和幂等 SQL 为准。
- 迁移不会删除或重建已有表，留言、收藏、举报和管理员数据保持原样。

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

- 安装完成后删除或禁止公网访问 `install.php`
- 生产环境请修改默认管理员密码；迁移不会重置该密码
- 确保 `uploads/` 目录有写入权限
- 建议配置 HTTPS 保障数据传输安全
