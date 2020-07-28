# newbpay-for-whmcs
Newebpay (蓝新金流) WHMCS 支付接口插件。

该插件允许您在 WHMCS 对接 Newebpay 进行收款。

使用方法：
1. 将 release 包上传至 WHMCS 根目录解压。
2. 进入 WHMCS 后台，找到 SETUP -> Payments -> Payment Gateways 。
3. 点选 All Payment Gateways , 找到 Newebpay（藍新金流）。
4. 依次填入 MerchantID (商店代號)、Hash Key、Hash IV，这些内容可在 Newebpay 后台创建和生成。
5. 勾选“测试模式”并保存以开始测试第一笔交易，若无问题，取消勾选即可正常收款。

Tips: 该插件采用 GNU GPLv3 开源协议，请遵守其协议使用。
