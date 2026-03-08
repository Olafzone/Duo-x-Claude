<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="margin: 0; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif; background-color: #f5f5f5;">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px;">
        <h1 style="margin: 0 0 20px 0; font-size: 22px; color: #000;">
            Nowy feedback: <?php echo esc_html($project->project_name); ?>
        </h1>

        <table style="width: 100%; margin-bottom: 20px; font-size: 14px;">
            <tr>
                <td style="padding: 8px 0; color: #666;">Klient:</td>
                <td style="padding: 8px 0;"><strong><?php echo esc_html($project->client_name); ?></strong></td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #666;">Email:</td>
                <td style="padding: 8px 0;"><?php echo esc_html($project->client_email); ?></td>
            </tr>
            <?php if (isset($feedback_data['q6_rating'])): ?>
            <tr>
                <td style="padding: 8px 0; color: #666;">Ocena:</td>
                <td style="padding: 8px 0;"><strong><?php echo intval($feedback_data['q6_rating']); ?>/10</strong></td>
            </tr>
            <?php endif; ?>
            <?php if (isset($feedback_data['q7_testimonial_permission'])): ?>
            <tr>
                <td style="padding: 8px 0; color: #666;">Testimonial:</td>
                <td style="padding: 8px 0;"><?php echo esc_html($feedback_data['q7_testimonial_permission']); ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

        <?php if (isset($feedback_data['q1_challenge'])): ?>
        <div style="margin-bottom: 20px;">
            <p style="margin: 0 0 5px 0; font-size: 12px; color: #999; text-transform: uppercase;">Glowne wyzwanie</p>
            <p style="margin: 0; font-size: 14px; line-height: 1.5;"><?php echo esc_html($feedback_data['q1_challenge']); ?></p>
        </div>
        <?php endif; ?>

        <?php if (isset($feedback_data['q2_surprise'])): ?>
        <div style="margin-bottom: 20px;">
            <p style="margin: 0 0 5px 0; font-size: 12px; color: #999; text-transform: uppercase;">Co zaskoczylo</p>
            <p style="margin: 0; font-size: 14px; line-height: 1.5;"><?php echo esc_html($feedback_data['q2_surprise']); ?></p>
        </div>
        <?php endif; ?>

        <?php if (isset($feedback_data['q3_transformation'])): ?>
        <div style="margin-bottom: 20px;">
            <p style="margin: 0 0 5px 0; font-size: 12px; color: #999; text-transform: uppercase;">Transformacja</p>
            <p style="margin: 0; font-size: 14px; line-height: 1.5;"><?php echo esc_html($feedback_data['q3_transformation']); ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($feedback_data['q8_referral'])): ?>
        <div style="margin-bottom: 20px; padding: 15px; background: #fff8e1; border-radius: 6px;">
            <p style="margin: 0 0 5px 0; font-size: 12px; color: #f57c00; text-transform: uppercase; font-weight: 600;">Potencjalny referral!</p>
            <p style="margin: 0; font-size: 14px; line-height: 1.5;"><?php echo esc_html($feedback_data['q8_referral']); ?></p>
        </div>
        <?php endif; ?>

        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

        <p style="margin: 0; font-size: 14px;">
            <a href="<?php echo esc_url(admin_url('admin.php?page=duo-feedback')); ?>" style="color: #000; font-weight: 600;">
                Zobacz pelne odpowiedzi w panelu &rarr;
            </a>
        </p>
    </div>
</body>
</html>
