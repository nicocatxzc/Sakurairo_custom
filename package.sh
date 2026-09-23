#!/usr/bin/env bash

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
NAME="Sakurairo"
OUT_DIR="$ROOT/temp"
STAGE="$OUT_DIR/.stage"
TARGET="$STAGE/$NAME"

log() { printf '\n\033[1m[package]\033[0m %s\n' "$*"; }
info() { printf '[package] %s\n' "$*"; }
die() {
    printf '[package] 错误: %s\n' "$*" >&2
    exit 1
}
need_cmd() { command -v "$1" >/dev/null 2>&1 || die "缺少命令: $1"; }

mkdir -p "$OUT_DIR"
LOG_FILE="$OUT_DIR/package.log"

exec > >(tee "$LOG_FILE") 2>&1

VERSION="$(sed -n 's/^[[:space:]]*[Vv]ersion:[[:space:]]*\([^[:space:]]*\).*/\1/p' "$ROOT/style.css" | head -n 1)"
VERSION="${VERSION:-0.0.0}"
ARCHIVE="$OUT_DIR/$NAME-$VERSION.zip"

cleanup() {
    local code=$?
    if ((code != 0)); then
        printf '[package] 打包失败（退出码 %s），暂存目录保留在: %s\n' "$code" "$STAGE" >&2
    fi
    exit "$code"
}
trap cleanup EXIT

need_cmd pnpm
need_cmd node
need_cmd zip

log "开始打包 $NAME $VERSION"
info "主题目录: $ROOT"
info "输出目录: $OUT_DIR"

for f in frontend/pnpm-lock.yaml frontend/pnpm-workspace.yaml inc/blocks/pnpm-lock.yaml inc/blocks/pnpm-workspace.yaml; do
    if [[ -e "$ROOT/$f" ]]; then
        die "检测到 $f：它会让 pnpm 忽略主题根的锁文件，请先删除后再执行本脚本"
    fi
done

log "安装 workspace 依赖（pnpm install --frozen-lockfile）"
(cd "$ROOT" && pnpm install --frozen-lockfile) ||
    die "依赖安装失败，如确认 lockfile 已过期可手动执行 pnpm install 后重试"

log "编译 frontend 与 inc/blocks（pnpm build）"
(cd "$ROOT" && pnpm build)

log "复制主题文件到暂存目录"
rm -rf "$STAGE"
mkdir -p "$TARGET"

tar -cf - -C "$ROOT" \
    --exclude=node_modules --exclude=.git --exclude=./temp --exclude='*.zip' \
    . | tar -xf - -C "$TARGET"

if command -v msgfmt >/dev/null 2>&1 && [[ -d "$ROOT/translation" ]]; then
    for po in "$ROOT"/translation/*.po; do
        [[ -e "$po" ]] || continue
        locale="$(basename "$po" .po)"
        if [[ ! -f "$TARGET/languages/$locale.mo" ]]; then
            mkdir -p "$TARGET/languages"
            info "编译翻译: translation/$locale.po → languages/$locale.mo"
            msgfmt -o "$TARGET/languages/$locale.mo" "$po"
        fi
    done
fi

log "剔除源码与开发态文件"
rm -rf "$TARGET/.git" "$TARGET/.github" "$TARGET/node_modules"
rm -f "$TARGET/.gitignore" "$TARGET/AGENTS.md" "$TARGET/package.sh" \
    "$TARGET/package.json" "$TARGET/pnpm-lock.yaml" "$TARGET/pnpm-workspace.yaml"

# frontend/ 只保留 PHP 组件模板与编译产物 dist/，其余（js/ts/vue/scss/json/types）都是源码
find "$TARGET/frontend" -type f -not -path "$TARGET/frontend/dist/*" ! -name '*.php' -delete
find "$TARGET/frontend" -mindepth 1 -type d -empty -not -path "$TARGET/frontend/dist*" -delete

# inc/blocks/ 只保留 PHP 与编译产物 build/
rm -rf "$TARGET/inc/blocks/src"
rm -f "$TARGET/inc/blocks/package.json" "$TARGET/inc/blocks/pnpm-lock.yaml" "$TARGET/inc/blocks/pnpm-workspace.yaml"

# .po / .pot 是翻译源文件，运行时只需要编译后的 .mo
find "$TARGET" -type f \( -name '*.po' -o -name '*.pot' \) -delete

find "$TARGET" -mindepth 1 -type d -empty -delete

STAGED_FILES="$(find "$TARGET" -type f | wc -l)"
info "包内文件数: $STAGED_FILES"

log "生成打包产物"
rm -f "$ARCHIVE"
(cd "$STAGE" && zip -rq -9 -X "$ARCHIVE" "$NAME")
rm -rf "$STAGE"

printf '\n\033[1m[package] 打包完成\033[0m\n'
printf '  版本:     %s\n' "$VERSION"
printf '  成品:     %s\n' "$ARCHIVE"
printf '  大小:     %s\n' "$(du -sh "$ARCHIVE" | cut -f 1)"
printf '  文件数:   %s\n' "$STAGED_FILES"
printf '  顶层目录: %s/（可直接在 WordPress 后台上传安装）\n' "$NAME"
printf '  日志:     %s\n' "$LOG_FILE"
