<template>
    <div class="flex flex-col items-center" v-if="status.user === 'success' && user">
      <div class="relative">
            <div class="w-100 h-64 overflow-hidden z-10">
                <UploadableImage 
                                    image-width="1500"
                                    image-height="500"
                                    location="cover"
                                    classes="object-cover w-full"
                                    alt="user background image"
                                    :user-image="user.data.attributes.cover_image" />
            </div>

            <div class="absolute flex items-center bottom-0 left-0 -mb-8 ml-12 z-20">
                <div class="w-32">
                     <UploadableImage 
                                    image-width="750"
                                    image-height="750"
                                    location="profile"
                                    classes="w-32 h-32 object-cover border-4 border-gray-200 rounded-full shadow-lg"
                                    alt="profile image for user"
                                    :user-image="user.data.attributes.profile_image" />
                     
                </div>
                <p  class="text-2xl text-gray-100 ml-4"> {{ user.data.attributes.name }} </p>
            </div>

            <div class="absolute flex items-center bottom-0 right-0 mb-4 mr-12 z-20">
                <button v-if="friendButtonText && friendButtonText !== 'Accept' " class="py-1 px-3 mr-2 bg-gray-400 rounded" 
                @click="$store.dispatch('sendFriendRequest', $route.params.userId)"
                >{{ friendButtonText }}</button>
            </div>
            <div class="absolute flex items-center bottom-0 right-0 mb-4 mr-12 z-20">
                <button v-if="friendButtonText && friendButtonText === 'Accept' " class="py-1 px-3 mr-2 bg-blue-500 rounded" 
                @click="$store.dispatch('acceptFriendRequest', $route.params.userId)"
                >Accept</button>

                
                <button v-if="friendButtonText && friendButtonText === 'Accept' " class="py-1 px-3 mr-2 bg-gray-400 rounded" 
                @click="$store.dispatch('ignoreFriendRequest', $route.params.userId)"
                >Ignore</button>
            </div>
        </div>
        
            <div v-if="status.post === 'loading'">Loading posts...</div>

            <div v-else-if="posts.length < 1">No posts found. Get started.</div>

            <Post v-else v-for="(post, postKey) in posts.data" :key="postKey" :post="post" />
    </div>
</template>

<script>
    import Post from '../../components/Post';
    import UploadableImage from "../../components/UploadableImage";
    import { mapGetters } from 'vuex';


    export default {
        name: "Show",

        components : {
            Post,
            UploadableImage,
        },


        mounted() {

            this.$store.dispatch('fetchUser', this.$route.params.userId);
            this.$store.dispatch('fetchUserPosts', this.$route.params.userId);
        },

        computed: {
            ...mapGetters({
                user: 'user',
                posts: 'posts',
                status: 'status',
                friendButtonText : 'friendButtonText',
            })
        }
    }
</script>

<style scoped>

</style>
