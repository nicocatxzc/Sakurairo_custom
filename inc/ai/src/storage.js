// 测试对话只保存在浏览器本地（IndexedDB），开新对话即删除上一条会话
const DB_NAME = "iro-ai";
const DB_VERSION = 1;
const STORE_NAME = "conversations";

function openDatabase() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = () => {
            const db = request.result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                db.createObjectStore(STORE_NAME, { keyPath: "id" });
            }
        };
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

// 读取最后保存的一条会话（按 id 倒序取第一条）
export async function readConversation() {
    const db = await openDatabase();

    return new Promise((resolve, reject) => {
        const transaction = db.transaction(STORE_NAME, "readonly");
        const cursorRequest = transaction.objectStore(STORE_NAME).openCursor(null, "prev");
        let conversation = null;

        cursorRequest.onsuccess = () => {
            if (cursorRequest.result && !conversation) {
                conversation = cursorRequest.result.value;
            }
        };
        transaction.oncomplete = () => {
            db.close();
            resolve(conversation);
        };
        transaction.onerror = () => {
            db.close();
            reject(transaction.error);
        };
    });
}

export async function putConversation(conversation) {
    const db = await openDatabase();

    return new Promise((resolve, reject) => {
        const transaction = db.transaction(STORE_NAME, "readwrite");
        transaction.objectStore(STORE_NAME).put(conversation);
        transaction.oncomplete = () => {
            db.close();
            resolve(true);
        };
        transaction.onerror = () => {
            db.close();
            reject(transaction.error);
        };
    });
}

// 删除全部会话，开启新对话前调用
export async function clearConversations() {
    const db = await openDatabase();

    return new Promise((resolve, reject) => {
        const transaction = db.transaction(STORE_NAME, "readwrite");
        transaction.objectStore(STORE_NAME).clear();
        transaction.oncomplete = () => {
            db.close();
            resolve(true);
        };
        transaction.onerror = () => {
            db.close();
            reject(transaction.error);
        };
    });
}
