<?php
namespace app\index\controller;

use app\common\model\ArticleCategory;
use app\common\model\Query;
use app\index\BaseController;
use think\model\Relation;

class Home extends BaseController
{

    public function index()
    {
        //home_list 推荐分类必须3个问题才能显示；
        $articleCategoryIds = \app\common\model\Questionnaires::where(['status'=>1])->group('article_category_id')->having('count(id) >= 1')->select()->column('article_category_id');
        $home_list = [];
        if($articleCategoryIds) {
            $home_list = ArticleCategory::whereIn('id', $articleCategoryIds)->order(['sort' => 'ASC'])->with(['questionnaires'=>function ($query) {
                $query->field('id, title, img_url, article_category_id, price, description')->limit(3); // 只选择指定字段并限制数量
            }])->select();
        }
        $this->success('', [
            'recommend' => \app\common\model\Questionnaires::getList(['status'=>1], ['id', 'title', 'img_url'], ['sort' => 'DESC'], 0, 4),
            'icon_list' => (new ArticleCategory())->getList(['status' => 1], ['id', 'title', 'icon_url'], ['sort' => 'ASC'], 0, 5),
            'home_list' => $home_list,
        ]);

    }
}
