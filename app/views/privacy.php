<?php
$months = (int) round(cfg('retention_days') / 30);
$privacyEmail = (string) cfg('privacy_email');
$contact = filter_var($privacyEmail, FILTER_VALIDATE_EMAIL)
    ? 'email <a href="mailto:' . e($privacyEmail) . '">' . e($privacyEmail) . '</a> or use the <a href="/#contact">contact form</a>'
    : 'use the <a href="/#contact">contact form</a> (choose "Something else")';
?>
<section class="section">
  <div class="wrap prose">
    <p class="eyebrow">Legal</p>
    <h1>Privacy policy</h1>
    <p class="muted">Last updated: 29 October 2026</p>

    <h2>Who is responsible</h2>
    <p>This website, manufrancis.com, is run by Manu Francis, who is the data controller for the personal data described here. For any privacy question or request, <?= $contact ?>.</p>

    <h2>The short version</h2>
    <ul>
      <li>No analytics, no advertising, no tracking pixels, no social media widgets.</li>
      <li>No cookies are set for visitors. Fonts, scripts and images are all served from this site, so your browser does not contact any third party.</li>
      <li>Personal data is collected only when you choose to send the contact form, and only to reply to you.</li>
    </ul>

    <h2>What is collected and why</h2>
    <table>
      <thead><tr><th>Data</th><th>Purpose</th><th>Legal basis (GDPR Art. 6)</th></tr></thead>
      <tbody>
        <tr><td>Name, email, optional phone and organisation, topic and message</td><td>To read and reply to your enquiry and to follow up on it</td><td>Your consent (6(1)(a)) and steps you ask for before a possible contract (6(1)(b))</td></tr>
        <tr><td>Date and time you gave consent, and the consent wording you saw</td><td>To be able to show that consent was given</td><td>Legal obligation to demonstrate consent (6(1)(c), Art. 7(1))</td></tr>
        <tr><td>A one-way keyed hash of your IP address (the IP itself is never stored)</td><td>To limit abuse and spam of the form; kept for at most 24 hours</td><td>Legitimate interest in keeping the site secure (6(1)(f))</td></tr>
        <tr><td>Standard web server logs kept by the hosting provider</td><td>Security and running the service</td><td>Legitimate interest (6(1)(f))</td></tr>
      </tbody>
    </table>
    <p>You do not have to give any data. Without name, email and message I cannot reply, so the form will not send without them.</p>

    <h2>How long data is kept</h2>
    <p>Enquiries are deleted automatically <?= $months ?> months after they are received, or earlier if you ask. Abuse-protection records are deleted within 24 hours.</p>

    <h2>Who receives it</h2>
    <p>Your data is never sold or shared for marketing. It is stored on servers of the website's hosting provider (Hostinger), which acts as a data processor under a data processing agreement. A copy of new enquiries is sent by email to Manu Francis so they can be answered. Data may be processed in India and in the countries where the hosting provider operates; where data leaves the EU/EEA, the provider's standard contractual clauses apply.</p>

    <h2>How it is protected</h2>
    <p>The site is served only over HTTPS. Enquiries are kept in a private database at the hosting provider that is not reachable from the web. Only Manu Francis can open them, through an admin area protected by a password, two-step verification and login limits.</p>

    <h2>Your rights</h2>
    <p>Under the GDPR (and India's Digital Personal Data Protection Act, 2023), you can:</p>
    <ul>
      <li>ask for a copy of the data held about you (access and portability);</li>
      <li>have it corrected or deleted;</li>
      <li>restrict or object to how it is used;</li>
      <li>withdraw your consent at any time. This does not affect what was done before you withdrew it.</li>
    </ul>
    <p>To use any of these rights, <?= $contact ?>. You will get an answer within one month. You can also complain to the data protection authority in your country.</p>

    <h2>Changes</h2>
    <p>If this policy changes, the new version will be posted here with a new date.</p>
  </div>
</section>
