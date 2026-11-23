import bcrypt from 'bcryptjs';
import { randomBytes } from 'node:crypto';

const [email, password] = process.argv.slice(2);
if (!email || !password) {
  console.error('Usage: npm run admin-user -- email@domain.com "password"');
  process.exit(1);
}
const hash = bcrypt.hashSync(password, 10).replace(/^\$2[ab]\$/, '$2y$');
console.log(`\nAdd to site-config.php:\n\n    'admin_users' => [\n        '${email.toLowerCase()}' => '${hash}',\n    ],\n    'session_secret' => '${randomBytes(32).toString('hex')}',\n`);
