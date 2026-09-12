<script setup>
import { watch } from "vue";
import api from "../../app/utils/api.js";
import CommentCard from "./vue/Card.vue";
import CommentForm from "./vue/Form.vue/index.js";
import Pagination from "../Pagination.vue";

const postId = _iro.page.post_id;
const totalComments = ref(0);
const totalPages = ref(0);
let comments = ref([]); // 评论列表
let page = ref(1); // 当前页
const hasNextPage = ref(false);
let firstloaded = false; // 初次加载标记，防止初次加载也滚动到评论区
const isLoading = ref(false);
let reply = ref({}); // 来自卡片的回复信息，传递给子组件
const commentTitle = useTemplateRef("comments-list-title");
async function getComments(currentPage = page.value) {
    isLoading.value = true;
    const { data } = await api.get(
        `${_iro.config.iro_api}/comments?post_id=${postId}&page=${currentPage}`,
    );
    if (firstloaded) {
        commentTitle.value.scrollIntoView({
            behavior: "smooth",
            block: "start",
        });
    }
    totalComments.value = data.totalComments;
    totalPages.value = data.totalPages;
    comments.value[currentPage - 1] = data?.comments;
    hasNextPage.value = data.hasNextPage;
    page.value = currentPage;
    isLoading.value = false;
    firstloaded = true;
}

function getReply(replyData) {
    reply.value = replyData;
}
function getSubmit(comment) {
    // 向当前列表插入成功提交的评论
    comments.value = [...comments.value, comment];
}

onMounted(async () => {
    await getComments(1);
});
watch(
    () => page.value,
    () => {
        getComments(page.value);
    },
);
</script>

<template>
    <h3 ref="comments-list-title" class="comment-list-title">
        Comments
        <span class="comment-count">{{ totalComments }} 条评论</span>
    </h3>
    <ul class="comment-list">
        <CommentCard
            v-for="(comment, index) in comments[page - 1]"
            :key="index"
            :comment="comment"
            @reply="getReply"
        />
    </ul>
    <Pagination
        v-if="totalPages > 1"
        layout="prev, pager, next"
        :page-size="1"
        :total="totalPages"
        @current-change="getComments"
        :disable="isLoading"
    />
    <CommentForm :post-id="postId" :reply="reply" @submit="getSubmit" />
</template>

<style scoped>
.comment-list-title {
    margin: 2rem auto;
    font-size: 1.25rem;
    font-weight: bold;
}

.comment-count {
    font-size: 0.8rem;
    color: #707070;
    margin-left: 0.65rem;
}

.comment-list {
    list-style: none;
}
</style>
