<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question'    => 'ما هي منصة بروميتريكا أكاديمي؟',
                'question_en' => 'What is Prometrica Academy?',
                'answer'      => 'منصة تعليمية متكاملة للصيادلة تقدّم دورات وامتحانات تجريبية وشهادات لمساعدتك على اجتياز امتحانات الترخيص والتفوّق مهنياً.',
                'answer_en'   => 'A complete e-learning platform for pharmacists offering courses, mock exams and certificates to help you pass licensing exams and excel professionally.',
            ],
            [
                'question'    => 'هل يمكنني تجربة المنصة قبل الاشتراك بباقة مدفوعة؟',
                'question_en' => 'Can I try the platform before subscribing to a paid plan?',
                'answer'      => 'نعم، تتوفّر باقة مجانية ودورات تجريبية تتيح لك استكشاف المنصة بالكامل قبل الترقية.',
                'answer_en'   => 'Yes, a free plan and sample courses let you fully explore the platform before upgrading.',
            ],
            [
                'question'    => 'كيف أحصل على الشهادة بعد إكمال الدورة؟',
                'question_en' => 'How do I get the certificate after completing a course?',
                'answer'      => 'بعد إكمال جميع دروس الدورة واجتياز الاختبارات، تُصدر شهادة احترافية يمكنك تحميلها بصيغة PDF من لوحة حسابك.',
                'answer_en'   => 'After completing all lessons and passing the quizzes, a professional PDF certificate is issued and downloadable from your account.',
            ],
            [
                'question'    => 'ما هي طرق الدفع المتاحة؟',
                'question_en' => 'What payment methods are available?',
                'answer'      => 'ندعم بطاقات الدفع الرئيسية وبوابات الدفع الإلكترونية. تظهر الخيارات المتاحة عند إتمام الاشتراك.',
                'answer_en'   => 'We support major payment cards and online payment gateways. Available options appear at checkout.',
            ],
            [
                'question'    => 'هل يمكنني الوصول إلى الدورات من الهاتف المحمول؟',
                'question_en' => 'Can I access courses from mobile?',
                'answer'      => 'بالتأكيد، المنصة متجاوبة بالكامل وتعمل بسلاسة على جميع الأجهزة والهواتف الذكية.',
                'answer_en'   => 'Absolutely. The platform is fully responsive and works smoothly on all devices and smartphones.',
            ],
            [
                'question'    => 'كم تدوم صلاحية الوصول للدورة بعد الشراء؟',
                'question_en' => 'How long does course access last after purchase?',
                'answer'      => 'تختلف مدة الوصول حسب الدورة أو الباقة؛ بعضها وصول دائم وبعضها لمدة محددة تظهر بوضوح قبل الشراء وفي لوحة اشتراكاتك.',
                'answer_en'   => 'Access duration varies by course or plan; some grant lifetime access and others a fixed period, shown clearly before purchase and in your subscriptions panel.',
            ],
        ];

        foreach ($faqs as $i => $faq) {
            Faq::firstOrCreate(
                ['question' => $faq['question']],
                $faq + ['is_active' => true, 'sort' => $i],
            );
        }
    }
}
