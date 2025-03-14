<?php
// src/Model/Table/UsersTable.php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class UsersTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        
        $this->setTable('users');
        $this->setPrimaryKey('id');
        
        $this->hasMany('EmailTemplates', [
            'foreignKey' => 'user_id',
        ]);
        
        $this->hasMany('AsinTemplates', [
            'foreignKey' => 'user_id',
        ]);
        
        $this->hasMany('ExcludedAsins', [
            'foreignKey' => 'user_id',
        ]);
    }
    
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');
            
        $validator
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email')
            ->add('email', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);
            
        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');
            
        $validator
            ->scalar('shop_name')
            ->maxLength('shop_name', 255)
            ->requirePresence('shop_name', 'create')
            ->notEmptyString('shop_name');
            
        $validator
            ->boolean('email_enabled')
            ->notEmptyString('email_enabled');
            
        $validator
            ->boolean('bcc_enabled')
            ->notEmptyString('bcc_enabled');
            
        $validator
            ->integer('email_send_hour_start')
            ->range('email_send_hour_start', [7, 23])
            ->notEmptyString('email_send_hour_start');
            
        $validator
            ->integer('email_send_hour_end')
            ->range('email_send_hour_end', [7, 23])
            ->notEmptyString('email_send_hour_end');
            
        $validator
            ->boolean('send_to_fba')
            ->notEmptyString('send_to_fba');
            
        $validator
            ->boolean('send_to_self_shipping')
            ->notEmptyString('send_to_self_shipping');
            
        $validator
            ->boolean('send_to_used_products')
            ->notEmptyString('send_to_used_products');
            
        $validator
            ->scalar('amazon_token')
            ->maxLength('amazon_token', 1024)
            ->allowEmptyString('amazon_token');
            
        return $validator;
    }
}

// src/Model/Table/EmailTemplatesTable.php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class EmailTemplatesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        
        $this->setTable('email_templates');
        $this->setPrimaryKey('id');
        
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
    }
    
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');
            
        $validator
            ->integer('user_id')
            ->notEmptyString('user_id');
            
        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');
            
        $validator
            ->scalar('subject')
            ->maxLength('subject', 255)
            ->requirePresence('subject', 'create')
            ->notEmptyString('subject');
            
        $validator
            ->scalar('content')
            ->requirePresence('content', 'create')
            ->notEmptyString('content');
            
        $validator
            ->integer('days_after_shipping')
            ->notEmptyString('days_after_shipping');
            
        return $validator;
    }
}

// src/Model/Table/AsinTemplatesTable.php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class AsinTemplatesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        
        $this->setTable('asin_templates');
        $this->setPrimaryKey('id');
        
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
    }
    
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');
            
        $validator
            ->integer('user_id')
            ->notEmptyString('user_id');
            
        $validator
            ->scalar('asin')
            ->maxLength('asin', 10)
            ->requirePresence('asin', 'create')
            ->notEmptyString('asin');
            
        $validator
            ->scalar('subject')
            ->maxLength('subject', 255)
            ->requirePresence('subject', 'create')
            ->notEmptyString('subject');
            
        $validator
            ->scalar('content')
            ->requirePresence('content', 'create')
            ->notEmptyString('content');
            
        $validator
            ->integer('days_after_shipping')
            ->notEmptyString('days_after_shipping');
            
        return $validator;
    }
}

// src/Model/Table/ExcludedAsinsTable.php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ExcludedAsinsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        
        $this->setTable('excluded_asins');
        $this->setPrimaryKey('id');
        
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
    }
    
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');
            
        $validator
            ->integer('user_id')
            ->notEmptyString('user_id');
            
        $validator
            ->scalar('asin')
            ->maxLength('asin', 10)
            ->requirePresence('asin', 'create')
            ->notEmptyString('asin')
            ->add('asin', 'unique', [
                'rule' => ['validateUnique', ['scope' => 'user_id']],
                'provider' => 'table'
            ]);
            
        return $validator;
    }
}

// src/Model/Table/OrdersTable.php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class OrdersTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        
        $this->setTable('orders');
        $this->setPrimaryKey('id');
        
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
    }
    
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');
            
        $validator
            ->integer('user_id')
            ->notEmptyString('user_id');
            
        $validator
            ->scalar('order_id')
            ->maxLength('order_id', 255)
            ->requirePresence('order_id', 'create')
            ->notEmptyString('order_id');
            
        $validator
            ->scalar('asin')
            ->maxLength('asin', 10)
            ->requirePresence('asin', 'create')
            ->notEmptyString('asin');
            
        $validator
            ->scalar('product_name')
            ->maxLength('product_name', 255)
            ->requirePresence('product_name', 'create')
            ->notEmptyString('product_name');
            
        $validator
            ->scalar('buyer_email')
            ->maxLength('buyer_email', 255)
            ->requirePresence('buyer_email', 'create')
            ->notEmptyString('buyer_email');
            
        $validator
            ->date('shipping_date')
            ->requirePresence('shipping_date', 'create')
            ->notEmptyDate('shipping_date');
            
        $validator
            ->boolean('is_fba')
            ->notEmptyString('is_fba');
            
        $validator
            ->boolean('is_used')
            ->notEmptyString('is_used');
            
        $validator
            ->boolean('send_email')
            ->notEmptyString('send_email');
            
        $validator
            ->boolean('email_sent')
            ->notEmptyString('email_sent');
            
        $validator
            ->dateTime('email_sent_date')
            ->allowEmptyDateTime('email_sent_date');
            
        return $validator;
    }
}

// src/Command/FetchAmazonOrdersCommand.php
namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\ORM\TableRegistry;
use Cake\Http\Client;
use Cake\Core\Configure;
use Cake\Cache\Cache;

class FetchAmazonOrdersCommand extends Command
{
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $io->out('Bắt đầu lấy dữ liệu đơn hàng từ Amazon...');
        
        $users = TableRegistry::getTableLocator()->get('Users')
            ->find()
            ->where(['amazon_token IS NOT' => null])
            ->all();
            
        foreach ($users as $user) {
            $io->out('Đang xử lý người dùng: ' . $user->name);
            
            // Kiểm tra xem token đã có trong cache chưa
            $cacheKey = 'amazon_orders_' . $user->id;
            
            if (!Configure::read('debug') && Cache::read($cacheKey, 'redis')) {
                $io->out('Đang sử dụng dữ liệu cache cho người dùng: ' . $user->id);
                continue;
            }
            
            // Gọi API Amazon để lấy đơn hàng (ví dụ)
            try {
                $orders = $this->fetchOrdersFromAmazon($user->amazon_token);
                
                $ordersTable = TableRegistry::getTableLocator()->get('Orders');
                
                foreach ($orders as $order) {
                    // Kiểm tra xem đơn hàng đã tồn tại trong cơ sở dữ liệu chưa
                    $existingOrder = $ordersTable->find()
                        ->where([
                            'user_id' => $user->id,
                            'order_id' => $order['order_id'],
                            'asin' => $order['asin']
                        ])
                        ->first();
                        
                    if (!$existingOrder) {
                        // Kiểm tra xem ASIN có nằm trong danh sách loại trừ không
                        $excludedAsin = TableRegistry::getTableLocator()->get('ExcludedAsins')
                            ->find()
                            ->where([
                                'user_id' => $user->id,
                                'asin' => $order['asin']
                            ])
                            ->first();
                            
                        if ($excludedAsin) {
                            $io->out('Bỏ qua ASIN bị loại trừ: ' . $order['asin']);
                            continue;
                        }
                        
                        // Xác định xem có nên gửi email không dựa trên cài đặt của người dùng
                        $shouldSendEmail = true;
                        
                        if ($order['is_fba'] && !$user->send_to_fba) {
                            $shouldSendEmail = false;
                        }
                        
                        if (!$order['is_fba'] && !$user->send_to_self_shipping) {
                            $shouldSendEmail = false;
                        }
                        
                        if ($order['is_used'] && !$user->send_to_used_products) {
                            $shouldSendEmail = false;
                        }
                        
                        $newOrder = $ordersTable->newEntity([
                            'user_id' => $user->id,
                            'order_id' => $order['order_id'],
                            'asin' => $order['asin'],
                            'product_name' => $order['product_name'],
                            'buyer_email' => $order['buyer_email'],
                            'shipping_date' => $order['shipping_date'],
                            'is_fba' => $order['is_fba'],
                            'is_used' => $order['is_used'],
                            'send_email' => $shouldSendEmail,
                            'email_sent' => false
                        ]);
                        
                        $ordersTable->save($newOrder);
                    }
                }
                
                // Lưu vào cache với thời gian sống 1 ngày
                Cache::write($cacheKey, true, [
                    'duration' => 86400,
                    'config' => 'redis'
                ]);
                
                $io->out('Đã cập nhật ' . count($orders) . ' đơn hàng cho người dùng: ' . $user->name);
            } catch (\Exception $e) {
                $io->error('Lỗi khi lấy dữ liệu cho người dùng ' . $user->name . ': ' . $e->getMessage());
            }
        }
        
        $io->out('Hoàn tất lấy dữ liệu đơn hàng từ Amazon.');
    }
    
    private function fetchOrdersFromAmazon($token)
    {
        // Đây là phương thức mẫu - cần thay thế bằng API thực của Amazon
        // Trong môi trường thực tế, bạn sẽ sử dụng Amazon MWS hoặc SP-API
        
        $client = new Client();
        
        // Ví dụ sử dụng Amazon Selling Partner API (SP-API)
        // URL và cấu trúc yêu cầu có thể thay đổi tùy theo API
        /*
        $response = $client->get('https://sellingpartnerapi-na.amazon.com/orders/v0/orders', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'x-amz-access-token' => $token
            ],
            'query' => [
                'CreatedAfter' => date('c', strtotime('-7 days')),
                'OrderStatuses' => 'Shipped'
            ]
        ]);
        
        $data = $response->getJson();
        
        // Xử lý dữ liệu đơn hàng
        $orders = [];
        foreach ($data['Orders'] as $order) {
            // Logic xử lý đơn hàng...
        }
        */
        
        // Giả lập dữ liệu đơn hàng cho mục đích minh họa
        return [
            [
                'order_id' => 'ORDER123',
                'asin' => 'B08ABCDEF',
                'product_name' => 'Sản phẩm mẫu 1',
                'buyer_email' => 'buyer1@example.com',
                'shipping_date' => date('Y-m-d'),
                'is_fba' => true,
                'is_used' => false
            ],
            [
                'order_id' => 'ORDER456',
                'asin' => 'B09ABCDEF',
                'product_name' => 'Sản phẩm mẫu 2',
                'buyer_email' => 'buyer2@example.com',
                'shipping_date' => date('Y-m-d'),
                'is_fba' => false,
                'is_used' => false
            ]
        ];
    }
}

// src/Command/SendReviewEmailsCommand.php
namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\ORM\TableRegistry;
use Cake\Mailer\Mailer;
use Cake\Mailer\TransportFactory;
use Cake\Core\Configure;
use Cake\Cache\Cache;

class SendReviewEmailsCommand extends Command
{
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $io->out('Bắt đầu gửi email yêu cầu đánh giá...');
        
        $currentHour = (int)date('G');
        
        // Lấy tất cả người dùng đã kích hoạt gửi email
        $users = TableRegistry::getTableLocator()->get('Users')
            ->find()
            ->where([
                'email_enabled' => true,
                'amazon_token IS NOT' => null
            ])
            ->all();
            
        foreach ($users as $user) {
            $io->out('Đang xử lý người dùng: ' . $user->name);
            
            // Kiểm tra thời gian gửi
            if ($currentHour < $user->email_send_hour_start || $currentHour > $user->email_send_hour_end) {
                $io->out('Bỏ qua: Ngoài khung giờ gửi email cho người dùng: ' . $user->name);
                continue;
            }
            
            // Lấy các đơn hàng cần gửi email
            $ordersTable = TableRegistry::getTableLocator()->get('Orders');
            $orders = $ordersTable->find()
                ->where([
                    'Orders.user_id' => $user->id,
                    'Orders.send_email' => true,
                    'Orders.email_sent' => false
                ])
                ->all();
                
            $io->out('Tìm thấy ' . $orders->count() . ' đơn hàng cần gửi email.');
            
            // Lấy các mẫu email của người dùng
            $emailTemplates = TableRegistry::getTableLocator()->get('EmailTemplates')
                ->find()
                ->where(['user_id' => $user->id])
                ->all()
                ->indexBy('days_after_shipping');
                
            // Lấy các mẫu email theo ASIN
            $asinTemplates = TableRegistry::getTableLocator()->get('AsinTemplates')
                ->find()
                ->where(['user_id' => $user->id])
                ->all();
                
            // Nhóm các mẫu ASIN theo ASIN và số ngày
            $asinTemplatesByAsin = [];
            foreach ($asinTemplates as $template) {
                if (!isset($asinTemplatesByAsin[$template->asin])) {
                    $asinTemplatesByAsin[$template->asin] = [];
                }
                $asinTemplatesByAsin[$template->asin][$template->days_after_shipping] = $template;
            }
            
            // Cache mật khẩu Amazon via Redis
            $amazonCreds = [];
            $amazonCredsKey = 'amazon_creds_' . $user->id;
            
            if (!Configure::read('debug') && Cache::read($amazonCredsKey, 'redis')) {
                $amazonCreds = Cache::read($amazonCredsKey, 'redis');
            } else {
                // Trong thực tế, bạn sẽ lấy thông tin đăng nhập từ Amazon API
                $amazonCreds = [
                    'sellerCentralEmail' => 'seller@example.com',
                    'amazonMarketplaceEmail' => 'marketplace@amazon.com'
                ];
                
                Cache::write($amazonCredsKey, $amazonCreds, [
                    'duration' => 3600, // 1 giờ
                    'config' => 'redis'
                ]);
            }
            
            foreach ($orders as $order) {
                // Tính số ngày sau khi gửi hàng
                $daysAfterShipping = (strtotime(date('Y-m-d')) - strtotime($order->shipping_date->format('Y-m-d'))) / 86400;
                
                // Tìm mẫu email phù hợp
                $template = null;
                
                // Ưu tiên mẫu theo ASIN
                if (isset($asinTemplatesByAsin[$order->asin][$daysAfterShipping])) {
                    $template = $asinTemplatesByAsin[$order->asin][$daysAfterShipping];
                }
                // Nếu không có mẫu ASIN, sử dụng mẫu chung
                elseif (isset($emailTemplates[$daysAfterShipping])) {
                    $template = $emailTemplates[$daysAfterShipping];
                }
                
                if ($template) {
                    $subject = $this->replaceVariables($template->subject, $order);
                    $content = $this->replaceVariables($template->content, $order);
                    
                    // Cache tình trạng email đã gửi trong Redis
                    $emailSentKey = 'email_sent_' . $order->id;
                    
                    if (!Configure::read('debug') && Cache::read($emailSentKey, 'redis')) {
                        $io->out('Email đã được gửi cho đơn hàng ' . $order->order_id . ' (từ cache)');
                        continue;
                    }
                    
                    try {
                        // Gửi email sử dụng API Amazon (giả định)
                        $this->sendViaAmazon(
                            $user,
                            $amazonCreds['amazonMarketplaceEmail'],
                            $order->buyer_email,
                            $subject,
                            $content
                        );
                        
                        // Cập nhật trạng thái đơn hàng
                        $order->email_sent = true;
                        $order->email_sent_date = date('Y-m-d H:i:s');
                        $ordersTable->save($order);
                        
                        // Lưu vào cache để tránh gửi lại
                        Cache::write($emailSentKey, true, [
                            'duration' => 86400 * 30, // 30 ngày
                            'config' => 'redis'
                        ]);
                        
                        $io->out('Đã gửi email cho đơn hàng: ' . $order->order_id);
                        
                        // Nếu BCC được kích hoạt, gửi một bản sao cho người dùng
                        if ($user->bcc_enabled) {
                            $this->sendBccToUser($user->email, $subject, $content, $order->order_id);
                            $io->out('Đã gửi BCC cho người dùng: ' . $user->email);
                        }
                    } catch (\Exception $e) {
                        $io->error('Lỗi khi gửi email cho đơn hàng ' . $order->order_id . ': ' . $e->getMessage());
                    }
                    
                    // Tạm dừng để không quá tải API
                    sleep(1);
                }
            }
        }
        
        $io->out('Hoàn tất gửi email yêu cầu đánh giá.');
    }
    
    private function replaceVariables($text, $order)
    {
        $replacements = [
            '%%注文ID%%' => $order->order_id,
            '%%商品名%%' => $order->product_name,
            '%%ASIN%%' => $order->asin
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
    
    private function sendViaAmazon($user, $fromEmail, $toEmail, $subject, $content)
    {
        // Trong môi trường thực tế, bạn sẽ sử dụng Amazon SES hoặc API thích hợp
        // Đây chỉ là mẫu giả lập
        
        /*
        $client = new \Aws\Ses\SesClient([
            'version' => 'latest',
            'region' => 'us-west-2',
            'credentials' => [
                'key' => Configure::read('Amazon.SES.key'),
                'secret' => Configure::read('Amazon.SES.secret'),
            ]
        ]);
        
        $client->sendEmail([
            'Source' => $fromEmail,
            'Destination' => [
                'ToAddresses' => [$toEmail]
            ],
            'Message' => [
                'Subject' => [
                    'Data' => $subject,
                    'Charset' => 'UTF-8'
                ],
                'Body' => [
                    'Html' => [
                        'Data' => $content,
                        'Charset' => 'UTF-8'
                    ]
                ]
            ]
        ]);
        */
        
        // Giả lập gửi email thành công
        return true;
    }
    
    private function sendBccToUser($email, $subject, $content, $orderId)
    {
        $mailer = new Mailer('default');
        $mailer
            ->setTo($email)
            ->setSubject('[BCC] ' . $subject . ' - Đơn hàng: ' . $orderId)
            ->setEmailFormat('html')
            ->deliver($content);
    }
}

// config/schema/amazon_review_system.sql
/*
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    shop_name VARCHAR(255) NOT NULL,
    email_enabled TINYINT(1) NOT NULL DEFAULT 0,
    bcc_enabled TINYINT(1) NOT NULL DEFAULT 0,
    email_send_hour_start INT NOT NULL DEFAULT 7,
    email_send_hour_end INT NOT NULL DEFAULT 23,
    send_to_fba TINYINT(1) NOT NULL DEFAULT 1,
    send_to_self_shipping TINYINT(1) NOT NULL DEFAULT 1,
    send_to_used_products TINYINT(1) NOT NULL DEFAULT 0,
    amazon_token TEXT,
    created DATETIME DEFAULT CURRENT_TIMESTAMP,
    modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    days_after_shipping INT NOT NULL,
    created DATETIME DEFAULT CURRENT_TIMESTAMP,
    modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE asin_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    asin VARCHAR(10) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    days_after_shipping INT NOT NULL,
    created DATETIME DEFAULT CURRENT_TIMESTAMP,
    modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE excluded_asins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    asin VARCHAR(10) NOT NULL,
    created DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, asin)
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id VARCHAR(255) NOT NULL,
    asin VARCHAR(10) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    buyer_email VARCHAR(255) NOT NULL,
    shipping_date DATE NOT NULL,
    is_fba TINYINT(1) NOT NULL DEFAULT 0,
    is_used TINYINT(1) NOT NULL DEFAULT 0,
    send_email TINYINT(1) NOT NULL DEFAULT 1,
    email_sent TINYINT(1) NOT NULL DEFAULT 0,
    email_sent_date DATETIME DEFAULT NULL,
    created DATETIME DEFAULT CURRENT_TIMESTAMP,
    modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
*/

// config/app_local.php (cấu hình Redis)
/*
return [
    'Cache' => [
        'default' => [
            'className' => 'Cake\Cache\Engine\FileEngine',
            'duration' => '+1 hours',
            'path' => CACHE,
            'prefix' => 'myapp_cake_core_',
            'serialize' => true
        ],
        
        'redis' => [
            'className' => 'Cake\Cache\Engine\RedisEngine',
            'host' => 'localhost',
            'port' => 6379,
            'password' => '',
            'database' => 0,
            'duration' => '+1 day',
            'prefix' => 'amazon_review_',
            'serialize' => true
        ],
    ],
    
    'Email' => [
        'default' => [
            'transport' => 'default',
            'from' => 'noreply@example.com',
        ],
    ],
    
    'TransportEmail' => [
        'default' => [