#!/usr/bin/env bash

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO="$(cd "$ROOT/.." && pwd)"
NAME="Sakurairo-hugo"
OUT_DIR="$ROOT/temp"
STAGE="$OUT_DIR/.stage"
TARGET="$STAGE/$NAME"
VERIFY="$OUT_DIR/.verify"
DIST="$REPO/frontend/dist"

BUILD_FRONTEND=1
VERIFY_BUILD=1

log() { printf '\n\033[1m[package]\033[0m %s\n' "$*"; }
info() { printf '[package] %s\n' "$*"; }
die() {
    printf '[package] 错误: %s\n' "$*" >&2
    exit 1
}
usage() {
    printf '用法: %s [--no-build] [--no-verify]\n' "$(basename "$0")"
    printf '  --no-build   复用已有的 frontend/dist，不再执行前端构建\n'
    printf '  --no-verify  不要求本地 hugo，跳过「用发布包自身构建一次」的验证\n'
}

for arg in "$@"; do
    case "$arg" in
        --no-build) BUILD_FRONTEND=0 ;;
        --no-verify) VERIFY_BUILD=0 ;;
        -h | --help)
            usage
            exit 0
            ;;
        *) die "未知参数: $arg（可用: --no-build --no-verify）" ;;
    esac
done

mkdir -p "$OUT_DIR"
LOG_FILE="$OUT_DIR/package.log"

exec > >(tee "$LOG_FILE") 2>&1

VERSION="$(sed -n 's/^[[:space:]]*[Vv]ersion:[[:space:]]*\([^[:space:]]*\).*/\1/p' "$REPO/style.css" 2>/dev/null | head -n 1)"
VERSION="${VERSION:-0.0.0}"
ARCHIVE="$OUT_DIR/$NAME-$VERSION.zip"

cleanup() {
    local code=$?
    if ((code != 0)) && [[ -d "$STAGE" ]]; then
        printf '[package] 打包失败（退出码 %s），暂存目录保留在: %s\n' "$code" "$STAGE" >&2
    fi
    exit "$code"
}
trap cleanup EXIT

[[ -f "$REPO/package.json" ]] || die "没找到仓库根 $REPO/package.json，这个脚本要在 Sakurairo 仓库内的 hugo/ 下运行"
command -v zip >/dev/null 2>&1 || die "缺少命令: zip"

# hugo 二进制只用于「发布包自包含」的验证，不是打包的必要条件
HUGO=""
for candidate in hugo "$HOME/go/bin/hugo.exe" "$HOME/go/bin/hugo"; do
    if command -v "$candidate" >/dev/null 2>&1; then
        HUGO="$(command -v "$candidate")"
        break
    fi
done
if ((VERIFY_BUILD)) && [[ -z "$HUGO" ]]; then
    info "没找到 hugo 可执行文件，跳过独立构建验证（zip 仍会生成）"
    VERIFY_BUILD=0
fi

log "开始打包 $NAME $VERSION"
info "主题目录: $ROOT"
info "输出目录: $OUT_DIR"

# 前端仍然是 WordPress 那一套 Vite 工程，这里只是替用户把这一步跑掉，产物归位到 frontend/dist
if ((BUILD_FRONTEND)); then
    command -v pnpm >/dev/null 2>&1 ||
        die "缺少 pnpm，无法构建前端（或用 --no-build 复用已有 $DIST）"
    log "构建前端（pnpm --filter frontend run build）"
    (cd "$REPO" && pnpm --filter frontend run build) || die "前端构建失败，上面的报错来自 vite"
else
    log "跳过前端构建（--no-build），复用 $DIST"
fi

[[ -f "$DIST/app.js" ]] || die "缺少 $DIST/app.js，构建产物不完整"
[[ -f "$DIST/style.css" ]] || die "缺少 $DIST/style.css，构建产物不完整"

log "复制模板与配置到暂存目录"
rm -rf "$STAGE" "$VERIFY"
mkdir -p "$TARGET/config"

# config/site 是本地站点快照（个人设置），不进发布包
cp -R "$ROOT/config/_default" "$TARGET/config/_default"
cp -R "$ROOT/layouts" "$TARGET/layouts"
cp -R "$ROOT/static" "$TARGET/static"
cp -R "$ROOT/archetypes" "$TARGET/archetypes"
cp -R "$ROOT/content" "$TARGET/content"
cp "$ROOT/README.md" "$TARGET/README.md"

# 主题信息文件：名称/作者/版本/许可都在主题头里（版本号也从这里取），跟着包走
cp "$REPO/style.css" "$TARGET/style.css"
cp "$REPO/LICENSE" "$TARGET/LICENSE"

# 公式语法测试页是开发期的样板，不作为起始内容发布
rm -f "$TARGET/content/posts/markdown-test.md"

log "把前端产物收进包内（assets/frontend/dist，同时挂载为 /frontend/dist/）"
mkdir -p "$TARGET/assets/frontend"
cp -R "$DIST" "$TARGET/assets/frontend/dist"

# 仓库里两个挂载指向 hugo/ 之外（../frontend、../frontend/dist），
# 发布包必须自包含，改成包内路径；第二条挂载的目标不变，仍发布到 /frontend/dist/
sed -i 's|source = "\.\./frontend/dist"|source = "assets/frontend/dist"|' "$TARGET/config/_default/hugo.toml"
sed -i 's|source = "\.\./frontend"|source = "assets/frontend"|' "$TARGET/config/_default/hugo.toml"

if grep -q 'source = "\.\./' "$TARGET/config/_default/hugo.toml"; then
    die "发布包里仍有指向仓库外部的挂载，请检查 config/_default/hugo.toml"
fi

if ((VERIFY_BUILD)); then
    log "用发布包自身构建一次（脱离仓库验证自包含）"
    "$HUGO" -s "$TARGET" -d "$VERIFY" --cleanDestinationDir --logLevel warn ||
        die "发布包独立构建失败，上面的报错来自 hugo"

    [[ -f "$VERIFY/index.html" ]] || die "构建产物缺少 index.html"
    grep -q 'frontend/dist/app.js' "$VERIFY/index.html" ||
        die "构建产物没有引用前端运行时，检查 [params.hugo] reuse_frontend_runtime 与 assets 挂载"
    [[ -f "$VERIFY/wp-json/sakura/v1/search_index" ]] ||
        die "构建产物缺少搜索索引 /wp-json/sakura/v1/search_index"

    PAGES="$(find "$VERIFY" -name '*.html' | wc -l | tr -d ' ')"
    info "独立构建通过：$PAGES 个 HTML 页面"
else
    info "已跳过独立构建验证，包内内容未经过 hugo 渲染确认"
fi

log "生成打包产物"
# 验证构建会在站点根留下 Hugo 自己的临时文件，不能进包
rm -rf "$TARGET/.hugo_build.lock" "$TARGET/resources" "$TARGET/public"
STAGED_FILES="$(find "$TARGET" -type f | wc -l | tr -d ' ')"
rm -f "$ARCHIVE"
(cd "$STAGE" && zip -rq -9 -X "$ARCHIVE" "$NAME")
rm -rf "$STAGE" "$VERIFY"

printf '\n\033[1m[package] 打包完成\033[0m\n'
printf '  版本:     %s\n' "$VERSION"
printf '  成品:     %s\n' "$ARCHIVE"
printf '  大小:     %s\n' "$(du -sh "$ARCHIVE" | cut -f 1)"
printf '  文件数:   %s\n' "$STAGED_FILES"
printf '  顶层目录: %s/（解压后直接 hugo server）\n' "$NAME"
printf '  验证:     %s\n' "$([[ $VERIFY_BUILD == 1 ]] && echo '已通过 hugo 独立构建' || echo '跳过')"
printf '  日志:     %s\n' "$LOG_FILE"
