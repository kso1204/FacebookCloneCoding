<template>
    <div class="flex flex-col items-center py-4">
        <NewPost/>

        <p v-if="newsStatus.postsStatus ==='loading'">Loading posts...</p>
        <Post v-else v-for="(post, postKey) in posts.data" :key="postKey" :post="post" />
    </div>
</template>

<script>
    import NewPost from '../components/NewPost';
    import Post from '../components/Post';
    import { mapGetters } from 'vuex';

    export default {
        name: "NewsFeed",

        components: {
            NewPost,
            Post,
        },

        mounted() {
           this.$store.dispatch('fetchNewsPosts');
        },

        computed: {
            ...mapGetters({
                posts: 'posts',
                newsStatus: 'newsStatus',
            })
        }
    }
</script>

<style scoped>

</style>
