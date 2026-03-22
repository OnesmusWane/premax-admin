<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LegalPagesSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('legal_pages')->upsert([

            // ── Privacy Policy ────────────────────────────────────────────
            [
                'type'           => 'privacy_policy',
                'title'          => 'Privacy Policy',
                'slug'           => 'privacy-policy',
                'version'        => '1.0',
                'effective_date' => $now,
                'is_active'      => true,
                'content'        => <<<HTML
<h2>Privacy Policy</h2>
<p><strong>Effective Date:</strong> {$now->format('d F Y')}</p>
<p>Premax Autocare &amp; Diagnostic Services ("we", "our", or "us") is committed to protecting your personal information. This Privacy Policy explains how we collect, use, and safeguard your data when you use our website or book our services.</p>

<h3>1. Information We Collect</h3>
<p>We may collect the following information when you interact with us:</p>
<ul>
  <li>Full name, phone number, and email address when you book a service or contact us.</li>
  <li>Vehicle registration number and make/model for service records.</li>
  <li>Messages and enquiries submitted through our contact form.</li>
  <li>Reviews and feedback you voluntarily submit.</li>
</ul>

<h3>2. How We Use Your Information</h3>
<ul>
  <li>To confirm and manage your service bookings.</li>
  <li>To respond to enquiries and provide customer support.</li>
  <li>To send booking confirmations and service reminders (via SMS or email).</li>
  <li>To improve our services based on customer feedback.</li>
  <li>We do <strong>not</strong> sell or share your personal data with third parties for marketing purposes.</li>
</ul>

<h3>3. Data Storage &amp; Security</h3>
<p>Your data is stored securely on our servers. We implement appropriate technical and organisational measures to protect your information against unauthorised access, alteration, disclosure, or destruction.</p>

<h3>4. Cookies</h3>
<p>Our website may use session cookies to improve your browsing experience. These are temporary and are deleted when you close your browser. We do not use tracking or advertising cookies.</p>

<h3>5. Your Rights</h3>
<p>You have the right to:</p>
<ul>
  <li>Request access to the personal data we hold about you.</li>
  <li>Request correction or deletion of your data.</li>
  <li>Withdraw consent for communications at any time.</li>
</ul>
<p>To exercise any of these rights, contact us at <a href="mailto:info@premaxautocare.co.ke">info@premaxautocare.co.ke</a> or call <a href="tel:+254742091794">+254 742 091 794</a>.</p>

<h3>6. Changes to This Policy</h3>
<p>We may update this Privacy Policy from time to time. Changes will be published on this page with an updated effective date. Continued use of our services after changes constitutes acceptance of the revised policy.</p>

<h3>7. Contact</h3>
<p>Premax Autocare &amp; Diagnostic Services<br>
Kiambu Road / Northern Bypass Junction, Next to Glee Hotel<br>
P.O. Box 58230-00200, Nairobi, Kenya<br>
Email: <a href="mailto:info@premaxautocare.co.ke">info@premaxautocare.co.ke</a><br>
Phone: +254 742 091 794 / +254 722 219 396</p>
HTML,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ── Terms of Service ──────────────────────────────────────────
            [
                'type'           => 'terms_of_service',
                'title'          => 'Terms of Service',
                'slug'           => 'terms-of-service',
                'version'        => '1.0',
                'effective_date' => $now,
                'is_active'      => true,
                'content'        => <<<HTML
<h2>Terms of Service</h2>
<p><strong>Effective Date:</strong> {$now->format('d F Y')}</p>
<p>By using the Premax Autocare &amp; Diagnostic Services website or booking our services, you agree to the following terms and conditions. Please read them carefully.</p>

<h3>1. Services Offered</h3>
<p>Premax Autocare &amp; Diagnostic Services provides automotive care including but not limited to: tyre services, wheel alignment, lubrication and oil changes, suspension repairs, car batteries, buffing, diagnostics, wiring, and panel beating. All prices are estimates unless otherwise stated and may vary based on the condition of the vehicle.</p>

<h3>2. Bookings &amp; Appointments</h3>
<ul>
  <li>Bookings made online or via phone are subject to availability and confirmation by our team.</li>
  <li>We reserve the right to reschedule appointments in the event of unforeseen circumstances.</li>
  <li>Customers are requested to arrive on time. Late arrivals may result in rescheduling.</li>
  <li>We reserve the right to decline service for vehicles that pose a safety risk to our staff.</li>
</ul>

<h3>3. Pricing</h3>
<ul>
  <li>All prices displayed on our website are estimates and are subject to change without prior notice.</li>
  <li>Final pricing is confirmed after vehicle inspection and before work commences.</li>
  <li>Additional work discovered during service will be communicated to the customer for approval before proceeding.</li>
</ul>

<h3>4. Liability</h3>
<ul>
  <li>Premax Autocare takes reasonable care of all vehicles in our custody. However, we are not liable for pre-existing damage, mechanical failures unrelated to services rendered, or items of value left in the vehicle.</li>
  <li>Customers are advised to remove valuables from their vehicle prior to drop-off.</li>
  <li>Our liability for any claim shall not exceed the cost of the service rendered.</li>
</ul>

<h3>5. Warranty</h3>
<p>Services and parts supplied by Premax Autocare carry a limited warranty as communicated at the time of service. Warranty claims must be reported within the specified period and are subject to inspection.</p>

<h3>6. Acceptable Use</h3>
<p>You agree not to use our website for any unlawful purpose or in a way that may harm Premax Autocare, its staff, or other customers. We reserve the right to refuse service to anyone who acts abusively or disrespectfully toward our team.</p>

<h3>7. Intellectual Property</h3>
<p>All content on this website, including logos, images, and text, is the property of Premax Autocare &amp; Diagnostic Services and may not be reproduced without written permission.</p>

<h3>8. Governing Law</h3>
<p>These terms are governed by the laws of the Republic of Kenya. Any disputes shall be subject to the jurisdiction of the courts of Nairobi, Kenya.</p>

<h3>9. Changes to Terms</h3>
<p>We may revise these Terms of Service at any time. Updates will be published on this page. Continued use of our services constitutes acceptance of the revised terms.</p>

<h3>10. Contact</h3>
<p>Premax Autocare &amp; Diagnostic Services<br>
Kiambu Road / Northern Bypass Junction, Next to Glee Hotel<br>
P.O. Box 58230-00200, Nairobi, Kenya<br>
Email: <a href="mailto:info@premaxautocare.co.ke">info@premaxautocare.co.ke</a><br>
Phone: +254 742 091 794 / +254 722 219 396</p>
HTML,
                'created_at' => $now,
                'updated_at' => $now,
            ],

        ], ['type'], ['title', 'content', 'version', 'effective_date', 'updated_at']);
    }
}