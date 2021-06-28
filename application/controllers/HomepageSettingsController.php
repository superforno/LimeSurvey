<?php

/**
 * Class HomepageSettingsController
 */
class HomepageSettingsController extends LSBaseController
{
    /**
     * @return array
     */
    public function accessRules()
    {
        return array(
            array(
                'allow',
                'actions' => array(),
                'users' => array('*'), //everybody
            ),
            array(
                'allow',
                'actions' => array('index',  'changeBoxesInContainer', 'createBox',
                    'deleteBox','resetAllBoxes' ,'toggleShowLastSurveyAndQuestion' ,
                    'updateBox', 'toggleShowLogoStatus', ''),
                'users' => array('@'), //only login users
            ),
            array('deny'),
        );
    }

    /**
     * Register js script before rendering
     *
     * @param string $view
     * @return bool
     */
    public function beforeRender($view)
    {
        App()->getClientScript()->registerScriptFile(App()->getConfig('adminscripts') . 'homepagesettings.js');
        return parent::beforeRender($view);
    }

    /**
     * Renders the index view (boxes gridView and settings switchers
     *
     * @return void
     */
    public function actionIndex()
    {
        if (!Permission::model()->hasGlobalPermission('settings', 'read')) {
            Yii::app()->setFlashMessage(gT('Access denied!'), 'error');
            $this->redirect($this->createUrl("/admin"));
        }

        $dataProviderBox = new CActiveDataProvider('Box');

        $this->aData = [
            'fullpagebar' => [
                'boxbuttons' => true,
                'returnbutton' => [
                    'url'=>'admin/index',
                    'text'=>gT('Back'),
                ],
            ],
            'pageTitle' => gT('Dashboard'),
        ];

        $this->render('index', [
            'dataProviderBox' => $dataProviderBox,
            'bShowLogo' => App()->getConfig('show_logo') == "show",
            'bShowLastSurveyAndQuestion' => App()->getConfig('show_last_survey_and_question') == "show",
            'bShowSurveyList' => App()->getConfig('show_survey_list') == "show",
            'bShowSurveyListSearch' => App()->getConfig('show_survey_list_search') == "show",
            'bBoxesInContainer' => App()->getConfig('boxes_in_container') == "yes",
            'iBoxesByRow' => (int) App()->getConfig('boxes_by_row'),
            'iBoxesOffset' => (int) App()->getConfig('boxes_offset'),
        ]);
    }

    /**
     * Renders the form view and create/save new box
     *
     * @return void
     */
    public function actionCreateBox()
    {
        if (!Permission::model()->hasGlobalPermission('settings', 'update')) {
            Yii::app()->setFlashMessage(gT('Access denied!'), 'error');
            $this->redirect($this->createUrl("/homepageSettings/index"));
        }

        $model = new Box();
        if (isset($_POST['Box'])) {
            if (Yii::app()->getConfig('demoMode')) {
                Yii::app()->setFlashMessage(gT('This setting cannot be changed because demo mode is active.'), 'error');
                $this->redirect($this->createUrl("/homepageSettings/index"));
            }
            $model->attributes = $_POST['Box'];
            if ($model->save()) {
                Yii::app()->user->setFlash('success', gT('New box created'));
                if (isset($_POST['saveandclose'])) {
                    $this->redirect(array('/homepageSettings/index'));
                } else {
                    $this->redirect(array('/homepageSettings/updateBox/id/' . $model->id));
                }
            } else {
                Yii::app()->user->setFlash('error', gT('Could not create new box'));
            }
        }

        $this->aData = [
            'pageTitle' => gT('New box'),
            'fullpagebar' => [
                'savebutton' => [
                    'form' => 'boxes-form',
                ],
                'saveandclosebutton' => [
                    'form' => 'boxes-form'
                ],
                'closebutton' => [
                    'url' => Yii::app()->createUrl('homepageSettings/index')
                ]
            ],
            'model' => $model,
        ];

        $this->render(
            'create',
            $this->aData
        );
    }

    /**
     * Update a box.
     *
     * @param int $id Box id
     * @return void
     * @throws CHttpException
     */
    public function actionUpdateBox($id)
    {
        if (!Permission::model()->hasGlobalPermission('settings', 'update')) {
            Yii::app()->setFlashMessage(gT('Access denied!'), 'error');
            $this->redirect(array('homepageSettings/index'));
        }
        if (Yii::app()->getConfig('demoMode')) {
            Yii::app()->setFlashMessage(gT('This setting cannot be changed because demo mode is active.'), 'error');
            $this->redirect(array('homepageSettings/index'));
        }

        $model = $this->loadModel($id);

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);

        if (isset($_POST['Box'])) {
            $model->attributes = $_POST['Box'];
            if ($model->save()) {
                Yii::app()->user->setFlash('success', gT('Box updated'));

                if (isset($_POST['saveandclose'])) {
                    $this->redirect(array('homepageSettings/index'));
                }
            } else {
                Yii::app()->user->setFlash('error', gT('Could not update box'));
            }
        }
        $this->aData = [
            'pageTitle' => gT('Update box ') . $model->title,
            'fullpagebar' => [
                'savebutton' => [
                    'form' => 'boxes-form',
                ],
                'saveandclosebutton' => [
                    'form' => 'boxes-form'
                ],
                'closebutton' => [
                    'url' => Yii::app()->createUrl('homepageSettings/index'),
                ],
             ],
            'model' => $model,
        ];

        $this->render('update', $this->aData);
    }

    /**
     * Deletes a box
     *
     * @param int $id
     * @return void
     * @throws CDbException
     * @throws CHttpException
     */
    public function actionDeleteBox($id = null)
    {
        $id = App()->request->getPost('id', $id);
        if (!Permission::model()->hasGlobalPermission('settings', 'update')) {
            Yii::app()->session['flashmessage'] = gT('Access denied!');
            $this->redirect(array('homepageSettings/index'));
        }
        if (Yii::app()->getConfig('demoMode')) {
            Yii::app()->setFlashMessage(gT('This setting cannot be changed because demo mode is active.'), 'error');
            $this->redirect(array('homepageSettings/index'));
        }

        $model = $this->loadModel($id);
        if ($model->delete()) {
            Yii::app()->user->setFlash('success', gT('Box deleted'));
        } else {
            Yii::app()->user->setFlash('error', gT('Could not delete Box'));
        }

        // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
        if (!isset($_GET['ajax'])) {
            $this->redirect(array('homepageSettings/index'));
        }
    }

    /**
     * Restores the default boxes entries and redirects to index
     *
     * @return void
     */
    public function actionResetAllBoxes()
    {
        if (Permission::model()->hasGlobalPermission('settings', 'update')) {
            // We delete all the old boxes, and reinsert new ones
            Box::model()->deleteAll();
            Box::model()->restoreDefaults();
        }
        $this->redirect(array('homepageSettings/index'));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     *
     * @param integer $id the ID of the model to be loaded
     * @return Box the loaded model
     * @throws CHttpException
     */
    public function loadModel($id)
    {
        $model = Box::model()->findByPk($id);
        if ($model === null) {
            throw new CHttpException(404, 'The requested page does not exist.');
        }
        return $model;
    }

    /**
     * Performs the AJAX update of box settings
     *
     * @param int $boxesbyrow
     * @param int $boxesoffset
     * @return bool
     */
    public function actionUpdateBoxesSettings($boxesbyrow, $boxesoffset)
    {
        if (Yii::app()->getConfig('demoMode')) {
            Yii::app()->setFlashMessage(gT('This setting cannot be changed because demo mode is active.'), 'error');
            $this->redirect(array('homepageSettings/index'));
        }
        if (Permission::model()->hasGlobalPermission('settings', 'update')) {
            SettingGlobal::setSetting('boxes_by_row', $boxesbyrow);
            SettingGlobal::setSetting('boxes_offset', $boxesoffset);
            return true;
        }
    }

    /**
     * Performs the AJAX toggle of show_logo setting
     */
    public function actionToggleShowLogoStatus()
    {
        if (Yii::app()->getConfig('demoMode')) {
            Yii::app()->setFlashMessage(gT('This setting cannot be changed because demo mode is active.'), 'error');
            $this->redirect(array('homepageSettings/index'));
        }
        if (Permission::model()->hasGlobalPermission('settings', 'update')) {
            $bNewShowLogo = (App()->getConfig('show_logo') == "show") ? "hide" : "show";
            SettingGlobal::setSetting('show_logo', $bNewShowLogo);
            echo $bNewShowLogo;
        }
    }

    /**
     * Performs the AJAX toggle of show_last_survey_and_question setting
     *
     * @return void
     */
    public function actionToggleShowLastSurveyAndQuestion()
    {
        if (Yii::app()->getConfig('demoMode')) {
            Yii::app()->setFlashMessage(gT('This setting cannot be changed because demo mode is active.'), 'error');
            $this->redirect(array('homepageSettings/index'));
        }
        if (Permission::model()->hasGlobalPermission('settings', 'update')) {
            $bNewShowLastSurveyAndQuestion = (App()->getConfig('show_last_survey_and_question') == "show") ? "hide" : "show";
            SettingGlobal::setSetting('show_last_survey_and_question', $bNewShowLastSurveyAndQuestion);
            echo $bNewShowLastSurveyAndQuestion;
        }
    }

    /**
     * Performs the AJAX toggle of show_survey_list
     *
     * @return void
     */
    public function actionToggleShowSurveyList()
    {
        if (Yii::app()->getConfig('demoMode')) {
            Yii::app()->setFlashMessage(gT('This setting cannot be changed because demo mode is active.'), 'error');
            $this->redirect(array('homepageSettings/index'));
        }

        if (Permission::model()->hasGlobalPermission('settings', 'update')) {
            $bShowSurveyList = (App()->getConfig('show_survey_list') == "show") ? "hide" : "show";
            SettingGlobal::setSetting('show_survey_list', $bShowSurveyList);
            echo $bShowSurveyList;
        }
    }

    /**
     * Performs the AJAX toggle of show_survey_list_search
     */
    public function actionToggleShowSurveyListSearch()
    {
        if (Yii::app()->getConfig('demoMode')) {
            Yii::app()->setFlashMessage(gT('This setting cannot be changed because demo mode is active.'), 'error');
            $this->redirect(array('homepageSettings/index'));
        }

        if (Permission::model()->hasGlobalPermission('settings', 'update')) {
            $bShowSurveyListSearch = (App()->getConfig('show_survey_list_search') == "show") ? "hide" : "show";
            SettingGlobal::setSetting('show_survey_list_search', $bShowSurveyListSearch);
            echo $bShowSurveyListSearch;
        }
    }

    /**
     * Performs the AJAX toggle of show_survey_list_search
     *
     * @return void
     */
    public function actionChangeBoxesInContainer()
    {
        if (Yii::app()->getConfig('demoMode')) {
            Yii::app()->setFlashMessage(gT('This setting cannot be changed because demo mode is active.'), 'error');
            $this->redirect(array('homepageSettings/index'));
        }

        if (Permission::model()->hasGlobalPermission('settings', 'update')) {
            $changeBoxesInContainer = (App()->getConfig('boxes_in_container')  == "yes") ? "no" : "yes";
            SettingGlobal::setSetting('boxes_in_container', $changeBoxesInContainer);
            echo $changeBoxesInContainer;
        }
    }

    /**
     * Manages all models.
     *
     * todo: remove this action, see comments below
     * Action is not accessible via homepagesetting site ..(user can't accees it)
     * Maybe it was for research/testing/experimental reason ?!?
     *
     * Furthermore it does not work (problems with breadcrumbs )
     */
    /*
    public function actionAdmin()
    {
        $model = new Box('search');
        $model->unsetAttributes(); // clear any default values
        if (isset($_GET['Box'])) {
            $model->attributes = $_GET['Box'];
        }

        $this->render('admin',[
            'model' => $model
        ]);
    } */

    /**
     * Performs the AJAX validation.
     *
     * @param Box $model the model to be validated
     * @return void
     */
    protected function performAjaxValidation($model)
    {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'boxes-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }
}
