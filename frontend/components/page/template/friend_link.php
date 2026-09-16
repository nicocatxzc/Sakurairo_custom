<div class="links">
    <ol class="categories">
        <template
            v-for="(category, index) in categories"
            :key="category?.id ?? index">
            <h3 :id="category?.name" class="category-title">
                {{ category?.name }}
            </h3>
            <div
                v-if="category.links.length"
                :key="category.id"
                class="category">
                <div
                    v-for="link in category.links"
                    :key="link.id"
                    class="link">
                    <a
                        :href="link.url"
                        target="_blank"
                        rel="noopener"
                        class="link-content flex-center">
                        <ElAvatar class="avatar">
                            <NuxtImg
                                v-if="link.image"
                                :placeholder="config?.missingAvatarPlaceholder ?? ''"
                                :src="link.image"
                                alt="" />
                        </ElAvatar>

                        <p class="name">{{ link.name }}</p>
                        <p class="desc">{{ link.description }}</p>
                    </a>
                </div>
            </div>
        </template>
    </ol>
</div>