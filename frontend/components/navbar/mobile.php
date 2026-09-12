<header
    ref="header"
    class="site-header mobile flex-center"
    :class="{
            bg: headerBg,
            hide: headerHide,
        }">
    <div class="menu-toggle flex-center" @click="toggleMenu('menu')">
        <Icon class="icon" :name="'ic:round-menu'"></Icon>
    </div>

    <div class="site-branding flex-center">
        <NuxtPicture
            class="nuxtpic flex-center"
            :src="themeConfig?.navLogo || ''"
            alt="site logo" />
        <NuxtLink :to="'/'">
            <span
                class="site-title"
                :style="{ fontFamily: themeConfig?.navTitleFont || '' }">
                {{ themeConfig?.navTitle }}
            </span>
        </NuxtLink>
    </div>

    <div v-if="themeConfig?.navbarUserMenu ?? true" class="user-toggle flex-center" @click="toggleMenu('user')">
        <Icon class="icon" :name="'fluent:bookmark-16-regular'"></Icon>
    </div>

    <nav ref="menuScope" class="menu-wrapper">
        <div v-if="themeConfig?.navbarSearch ?? true" class="search-form">
            <ElInput
                v-model="searchKeyword"
                class="search-input"
                placeholder="想找点什么呢?"
                :prefix-icon="Search"
                inputmode="search"
                @keyup.enter="gotoSearch()" />
        </div>
        <ul
            class="menu"
            :style="{
                    fontFamily: themeConfig?.navOptionFont || '',
                }">
            <li
                v-for="(item, index) in menuItems"
                :key="index"
                class="item">
                <div class="item-head">
                    <NuxtLink class="link" :to="item.url">
                        {{ item.title }}
                    </NuxtLink>
                    <Icon
                        v-if="item.children && item.children.length"
                        class="button"
                        :class="{
                                expand: expandedMenuItem == index,
                            }"
                        :name="'fa7-solid:angle-right'"
                        @click="toggleMenuItem(index)" />
                </div>
                <template v-if="item.children && item.children.length">
                    <ul
                        class="sub-menu"
                        :class="{
                                expand: expandedMenuItem == index,
                            }">
                        <li v-for="child in item.children" :key="child.id">
                            <NuxtLink :to="child.url">
                                {{ child.title }}
                            </NuxtLink>
                        </li>
                    </ul>
                </template>
            </li>
        </ul>
    </nav>

    <ClientOnly>
        <div v-if="themeConfig?.navbarUserMenu ?? true" ref="userScope" class="user-wrapper">
            <div class="user-menu-container">
                <div class="user-menu flex-center">
                    <ElAvatar size="default" class="avatar">
                        <NuxtPicture
                            :src="getUserAvatar(user?.avatar)"
                            alt="navbar avatar"
                            class="nuxtpic" />
                    </ElAvatar>
                    <div class="user-info">
                        <span class="name">{{
                                user?.role ? user.name : "游客"
                            }}</span>
                    </div>
                </div>
                <div v-if="user?.role" class="user-option">
                    <a
                        v-if="user?.management?.admin"
                        :href="user?.management?.admin"
                        target="_blank">
                        管理后台
                    </a>
                    <NuxtLink
                        v-if="user?.role == 'administrator'"
                        :to="'/dashboard'">
                        主题设置
                    </NuxtLink>
                    <a
                        v-if="user?.management?.newpost"
                        :href="user?.management?.newpost"
                        target="_blank">
                        撰写文章
                    </a>
                    <a href="#" target="_top" @click="authStore.clearAuth()">
                        退出登录
                    </a>
                </div>
                <div v-else class="visitor-option flex-center">
                    <a
                        href="#"
                        aria-label="点击登录"
                        @click="openLoginForm">
                        登录
                    </a>
                </div>
            </div>
        </div>
    </ClientOnly>
</header>