<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Media;
use App\Models\News;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Seven sample articles for the public news page.
 *
 * 🔴 updateOrCreate keyed on the slug, and the media row keyed on its parent.
 * Re-running a seeder must not duplicate content — this one has run before in
 * every environment it will run in again.
 *
 * ⚠️ Covers point at REPOSITORY files under public/images, not at uploads.
 * That is why NewsController::deleteFile refuses to unlink anything without
 * the 'storage/' prefix: deleting one of these would remove it from the
 * working tree and no environment would get it back.
 */
class NewsSeeder extends Seeder
{
    public function run(): void
    {
        $author = $this->author();

        foreach ($this->articles() as $index => $article) {
            $news = News::updateOrCreate(
                ['slug' => $article['slug']],
                [
                    'author_id' => $author?->id,
                    'title' => $article['title'],
                    'summary' => $article['summary'],
                    'content' => $article['content'],
                    'is_published' => true,
                    // Spread across recent weeks so the list has a real
                    // ordering to show rather than seven identical timestamps.
                    'published_at' => now()->subDays($index * 6 + 1),
                ]
            );

            $this->attachCover($news, $article['image']);
        }
    }

    /**
     * The by-line for seeded articles.
     *
     * ⚠️ A REAL admin account, found rather than created: this seeder runs
     * after RoleSeeder and the admin accounts it needs already exist. Creating
     * one here would produce a second staff account nobody asked for.
     *
     * Null is fine — author_id is nullable and the page falls back to the
     * platform's name, so seeding a fresh database with no admin still works.
     */
    private function author(): ?User
    {
        return User::query()
            ->where('userable_type', Admin::class)
            ->orderBy('id')
            ->first();
    }

    /**
     * Attaches the cover through the polymorphic relation.
     *
     * 🔴 updateOrCreate on the relation, not create: re-running the seeder
     * must refresh the one cover, not stack a second media row per article.
     */
    private function attachCover(News $news, string $path): void
    {
        // ⚠️ A missing artwork file is skipped rather than recorded: a media
        // row pointing at nothing renders a broken image on the public page,
        // which is worse than the card's own placeholder.
        if (! File::exists(public_path($path))) {
            return;
        }

        $news->media()->updateOrCreate(
            ['mediable_id' => $news->id, 'mediable_type' => News::class],
            [
                'path' => $path,
                'type' => 'image',
                'alt_text' => $news->title,
                'display_order' => 1,
            ]
        );
    }

    /** @return array<int, array{slug: string, title: string, summary: string, content: string, image: string}> */
    private function articles(): array
    {
        return [
            [
                'slug' => 'tawjihi-exam-dates-2026',
                'title' => 'إعلان مواعيد امتحانات التوجيهي للدورة الأولى 2026',
                'summary' => 'أعلنت وزارة التربية والتعليم الجدول الرسمي لامتحانات الثانوية العامة، ويبدأ التطبيق مطلع حزيران لجميع الفروع.',
                'content' => "أعلنت وزارة التربية والتعليم العالي الجدول الرسمي لامتحانات شهادة الدراسة الثانوية العامة للدورة الأولى من العام 2026، بعد اعتماده في اجتماع اللجنة العليا للامتحانات.\n\nتبدأ الامتحانات في السادس من حزيران وتستمر حتى الخامس والعشرين منه، أي ثلاثة أسابيع كاملة تشمل جميع الفروع الأكاديمية والمهنية.\n\nأبرز ما في الجدول هذا العام أن الوزارة اعتمدت جلسة امتحانية واحدة يومياً بدل جلستين، وتبدأ الجلسة في التاسعة صباحاً. الهدف المعلن هو تخفيف الضغط على الطلبة في الأيام التي كانت تحمل مادتين.\n\nأما توزيع العلامات فلم يطرأ عليه تغيير: ثلاثون بالمئة لامتحان الفصل الأول وسبعون بالمئة للامتحان النهائي.\n\nونبّهت الوزارة إلى أن الآلة الحاسبة العلمية مسموحة في امتحانَي الفيزياء والكيمياء دون سواهما، وأن أي جهاز آخر يُعدّ مخالفة.",
                'image' => 'images/img/blog/4.jpg',
            ],
            [
                'slug' => 'math-curriculum-update',
                'title' => 'تعديلات على منهاج الرياضيات للفرع العلمي',
                'summary' => 'حُذفت وحدة المصفوفات من مقرر الفصل الثاني، وأُضيفت تطبيقات عملية على التكامل المحدّد.',
                'content' => "أصدرت الإدارة العامة للمناهج تعميماً بتعديلات على مقرر الرياضيات للفرع العلمي، تسري اعتباراً من الفصل الدراسي الثاني.\n\nالتعديل الأبرز هو حذف وحدة المصفوفات بالكامل من مقرر الفصل الثاني. الوحدة كانت تُدرَّس في أربع حصص ولم تكن مرتبطة بباقي المقرر ارتباطاً وثيقاً.\n\nفي المقابل أُضيفت وحدة تطبيقات على التكامل المحدّد، تركّز على حساب المساحات والحجوم في مسائل من الفيزياء والهندسة، وهو ما طالب به معلّمو المادة منذ عامين.\n\nحدّثنا محاضرات المنصّة لتواكب التعديل: الوحدة المحذوفة نُقلت إلى قسم «مواد إثرائية» لمن يرغب، والوحدة الجديدة مصوّرة بالكامل مع كويزاتها.",
                'image' => 'images/img/blog/1.jpg',
            ],
            [
                'slug' => 'library-launch',
                'title' => 'إطلاق المكتبة المنظّمة لجميع الفروع',
                'summary' => 'أوراق العمل والملخّصات والامتحانات السابقة صارت مجمّعة بفرعك ومادتك، بدل تشتّتها بين المجموعات.',
                'content' => "أطلقنا اليوم مكتبة المنصّة، وهي مساحة واحدة تجمع كل الملفات التي يحتاجها طالب التوجيهي، مرتّبة بحسب الفرع ثم المادة ثم النوع.\n\nالمشكلة التي تحلّها المكتبة معروفة لكل طالب: الملخّصات وأوراق العمل والامتحانات السابقة موجودة، لكنها متناثرة بين عشرات المجموعات، ولا أحد يعرف أيّها محدَّث وأيّها لسنة مضت.\n\nكل ملف في المكتبة مرتبط بمادته ومعلّمها، ويحمل تاريخ إضافته بوضوح. والملفات متاحة لطلبة الفرع المشتركين في المادة، تماماً كالمحاضرات.\n\nنبدأ بالفرعين العلمي والأدبي، ويلحق بهما التجاري والصناعي خلال الأسابيع القادمة.",
                'image' => 'images/img/blog/5.jpg',
            ],
            [
                'slug' => 'study-plan-tips',
                'title' => 'كيف تبني خطة مراجعة لا تنهار بعد أسبوع',
                'summary' => 'الخطة التي تنجح ليست الأطول بل الأقدر على الاستمرار — أربع قواعد عملية من تجربة طلبة سابقين.',
                'content' => "كل عام يبدأ آلاف الطلبة خططاً للمراجعة، ومعظم هذه الخطط ينهار في أسبوعها الثاني. السبب في الغالب ليس الكسل، بل أن الخطة نفسها كانت مستحيلة منذ كُتبت.\n\nالقاعدة الأولى: خطّط بالساعات لا بالصفحات. «أدرس ثلاث ساعات» هدف تتحكّم به، أما «أنهي أربعين صفحة» فيتوقّف على صعوبة الصفحات نفسها.\n\nالقاعدة الثانية: اترك يوماً فارغاً كل أسبوع. ليس للراحة فحسب، بل لتعويض ما تأخّر — الخطة بلا فسحة تنهار عند أول يوم يمرض فيه صاحبها.\n\nالقاعدة الثالثة: راجع القديم قبل أن تبدأ الجديد. عشرون دقيقة في أول الجلسة لمراجعة جلسة الأمس تضاعف ما يبقى في الذاكرة.\n\nالقاعدة الرابعة: قِس بالاختبار لا بالشعور. الشعور بالفهم خدّاع، والكويز بعد كل محاضرة هو ما يخبرك بالحقيقة.",
                'image' => 'images/img/blog/3.jpg',
            ],
            [
                'slug' => 'physics-lab-videos',
                'title' => 'إضافة فيديوهات المختبر لمادة الفيزياء',
                'summary' => 'تجارب المنهاج مصوّرة خطوة بخطوة، لطلبة المدارس التي لا تتوفّر فيها مختبرات مجهّزة.',
                'content' => "أضفنا إلى مادة الفيزياء للفرع العلمي سلسلة فيديوهات تغطّي تجارب المنهاج العملية كاملة، مصوّرة في مختبر حقيقي وبالأدوات نفسها التي يذكرها الكتاب.\n\nالدافع إلى ذلك عملي: عدد كبير من المدارس لا يملك مختبراً مجهّزاً، وطلبتها يُمتحنون في تجارب لم يروها إلا مرسومة في الكتاب.\n\nكل فيديو يعرض التجربة خطوة بخطوة: الأدوات، والخطوات، والقراءات المتوقّعة، ومصادر الخطأ الشائعة في القياس — وهي بالضبط ما تدور حوله أسئلة الامتحان.\n\nالفيديوهات مرتبطة بمحاضرات الوحدات المقابلة لها، وتظهر لطلبة الفرع العلمي المشتركين في المادة.",
                'image' => 'images/img1/header.jpg',
            ],
            [
                'slug' => 'english-exam-format',
                'title' => 'تغيير في نمط أسئلة اللغة الإنجليزية',
                'summary' => 'سؤال المقال يُستبدل بسؤالَي فهم مقروء، مع بقاء التوزيع الكلي للعلامات كما هو.',
                'content' => "اعتمدت الوزارة تعديلاً على نمط أسئلة امتحان اللغة الإنجليزية للثانوية العامة، يبدأ العمل به من الدورة الأولى القادمة.\n\nالتعديل يستبدل سؤال المقال الطويل بسؤالَي فهم مقروء، كل منهما يتبع نصّاً قصيراً بأسئلة مباشرة واستنتاجية.\n\nالتوزيع الكلي للعلامات لم يتغيّر، لكن توزيعها داخل الورقة تغيّر: ما كان مخصّصاً للمقال صار موزّعاً على السؤالين الجديدين.\n\nالمعنى العملي للطالب أن التحضير يتحوّل من حفظ قوالب إنشائية إلى تمرين حقيقي على القراءة السريعة واستخراج المعنى، وهو ما تركّز عليه محاضرات المادة المحدَّثة على المنصّة.",
                'image' => 'images/img/blog/2.jpg',
            ],
            [
                'slug' => 'exam-anxiety',
                'title' => 'قلق الامتحان: ما ينفع فعلاً وما يزيده سوءاً',
                'summary' => 'القلق قبل الامتحان طبيعي، لكن بعض ما ينصح به الناس يضاعفه بدل أن يخفّفه.',
                'content' => "القلق قبل الامتحان ليس عطلاً في الطالب، بل استجابة طبيعية لموقف مهم فعلاً. المشكلة تبدأ حين يتحوّل إلى ما يمنع الدراسة نفسها.\n\nأكثر ما يزيده سوءاً هو السهر الطويل قبل الامتحان بيوم. النوم أقل من ستّ ساعات يضعف الاسترجاع من الذاكرة، فيدخل الطالب القاعة وقد نسي ما كان يعرفه فعلاً.\n\nومثله المقارنة بالآخرين. مجموعات الدراسة التي يعلن فيها كل طالب كم أنجز تنتج ضغطاً بلا فائدة، لأن الوتيرة تختلف من شخص لآخر ومن مادة لأخرى.\n\nما ينفع عملياً: امتحان تجريبي بشروط حقيقية — وقت محدّد، بلا هاتف، بلا انقطاع. القلق ينخفض حين يصير الموقف مألوفاً، لا حين يُطلب من الطالب ألّا يقلق.\n\nوإذا وصل القلق إلى منع النوم أو الأكل لأيام، فهذه ليست مسألة تنظيم وقت، ومراجعة المرشد النفسي في المدرسة خطوة عملية لا اعتراف بالضعف.",
                'image' => 'images/img/blog/6.jpg',
            ],
        ];
    }
}
