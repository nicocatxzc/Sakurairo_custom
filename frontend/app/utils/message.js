import 'element-plus/es/components/message/style/css'
import { ElMessage } from 'element-plus'
_iro.message=(message,type="info")=>{
    ElMessage({
        dangerouslyUseHTMLString:true,
        message:message,
        type:type,
        showClose: true,
    })
}