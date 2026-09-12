import axios from "axios";
import { setupCache } from "axios-cache-interceptor";

const api = setupCache(axios.create(), {
    ttl: 1000 * 60 * 5,
    methods: ["get"],
});

export default api;
