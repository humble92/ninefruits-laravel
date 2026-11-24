<?php

return [
    'forbidden' => [
        'title' => '访问受限',
        'message' => '当前账号无权打开此页面或查看此资料。',
    ],
    'not_found' => [
        'title' => '页面或资料不可用',
        'message' => '页面地址可能已更改，或当前账号无法查看所请求的资料。',
    ],
    'certificate_pdf' => [
        'title' => '证书 PDF 不可用',
        'message' => '证书签发记录存在，但无法打开对应的 PDF 文件。',
    ],
    'page_expired' => [
        'title' => '页面已过期',
        'message' => '页面打开时间过长或登录状态已变更，请求未能完成。请返回上一页，刷新后重试。',
        'reload_prompt' => '要刷新页面吗？未保存的内容将会丢失。',
    ],
    'too_many_requests' => [
        'title' => '请求过多',
        'message' => '短时间内发送的请求过多。请稍后再试。',
    ],
    'server_error' => [
        'title' => '出现问题',
        'message' => '处理请求时出现问题。如果问题持续，请提供下方的参考编号联系相关部门。',
    ],
    'unavailable' => [
        'title' => '暂时无法使用',
        'message' => '系统正在维护或暂时无法使用。请稍后再试。',
    ],
    'code' => '错误代码 :code',
    'reference' => '参考编号',
];
