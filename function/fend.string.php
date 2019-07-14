<?php

/**
 * 字符串处理
 *
 * */
class GendString
{

    /**
     * 过滤掉标签
     * @param      string 源串
     * @param null $tags
     *
     * @return string
     */
    public static function filterContent($text, $tags = null)
    {
        $text = htmlentities($text, ENT_NOQUOTES, 'UTF-8');
        return $text;
    }

    /**
     * 追加URL参数
     * 检测是?&追加
     *
     * @param  string $str url地址
     * @param  string $pars 需要追加的参数
     * @return string
     * */
    public static function subUrl($str, $pars)
    {
        if (false === strpos($str, '?')) {
            $str .= '?';
        } else {
            $str .= '&';
        }
        $str .= $pars;

        return $str;
    }

    /**
     * @param      $idStr id串  1,2,3,4
     * @param      $id        要加入的id
     * @param bool $reverse 加载的位置
     *
     * @return string
     */
    public static function addIdToIdStr($idStr, $id, $reverse = false)
    {
        if ($reverse) {
            return $idStr ? $idStr . ',' . $id : $id;
        }
        return $idStr ? $id . ',' . $idStr : $id;
    }

    /**
     * 移除串中ID
     * @param $idStr
     * @param $id
     *
     * @return string
     */
    public static function removeIdFromIdStr($idStr, $id)
    {
        $idStr = ',' . $idStr . ',';
        return trim(str_replace(",{$id},", ',', $idStr), ',');
    }

    /**
     * 判断ID是否在串中
     * @param $idStr
     * @param $id
     *
     * @return bool
     */
    public static function isExistInIdStr($idStr, $id)
    {
        $idStr = ',' . $idStr . ',';
        return (strpos($idStr, ",{$id},") !== false);
    }

    /**
     * 处理ID串为半角","分隔
     * @param $id_str
     *
     * @return string
     */
    public static function getIdStr($id_str)
    {
        if (empty($id_str))
            return;
        $id_str = trim(str_replace("，", ',', $id_str), ',');
        $id_ary = @explode(',', $id_str);
        if (!empty($id_ary)) {
            foreach ($id_ary as &$id) {
                $id = (int)$id;
            }
        }

        return !empty($id_ary) ? implode(',', $id_ary) : '';
    }

    /**
     * 文本入库前的过滤工作
     * @param      $textString
     * @param bool $htmlspecialchars
     *
     * @return string
     */
    public static function getSafeText($textString, $htmlspecialchars = true)
    {
        return $htmlspecialchars ? htmlspecialchars(trim(strip_tags(self::qj2bj($textString)))) : trim(strip_tags(self::qj2bj($textString)));
    }

    /**
     * XML转换
     * @param $string
     *
     * @return mixed|string
     */
    public static function getSafeXml($string)
    {
        return self::getSafeUtf8(self::getSafeText($string), $_htmlspecialchars = true);
    }

    /**
     * UTF8转换
     * @param $content
     *
     * @return mixed|string
     */
    public static function getSafeUtf8($content)
    {
        $content = mb_convert_encoding($content, 'gbk', 'utf-8');
        $content = mb_convert_encoding($content, 'utf-8', 'gbk');
        $content = preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', '', $content);
        return $content;
    }

    /**
     * 国标转换
     * @param $content
     *
     * @return mixed|string
     */
    public static function getSafeGbk($content)
    {
        $content = mb_convert_encoding($content, 'utf-8', 'gbk');
        $content = preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', '', $content);
        return $content;
    }

    //加密 cookie
    public static function docode($string, $operation = 'de', $key = '', $expiry = 0, $ckey_length = 10)
    {
        $result = null;
        //$ckey_length = 10;//随机密钥
        $key = md5($key ? $key : FDKEY); //取得密钥MD5码
        $keya = md5(substr($key, 0, 16)); //密钥MD5的前16位
        $keyb = md5(substr($key, 16, 16)); //密钥MD5的后16位

        $keyc = $ckey_length ? ($operation == 'de' ? substr($string, 0, $ckey_length) : substr(md5(microtime()),
            -$ckey_length)) : '';
        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);

        $string = $operation == 'de' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d',
                $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($string);

        $box = range(0, 255);
        $rndkey = array();
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }
        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        if ($operation == 'de') {
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result,
                        26) . $keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc . str_replace('=', '', base64_encode($result));
        }
    }

    /**
     * 获取10阶的hash
     * @param $pBloggerID
     *
     * @return int
     */
    public static function hashDBID($pBloggerID)
    {
        return floor($pBloggerID / 10) % 10;
    }

    /**
     * 取模
     * @param $pBloggerID
     *
     * @return int
     */
    public static function hashTBLID($pBloggerID)
    {
        return $pBloggerID % 10;
    }

    /**
     * 全角转半角
     * @param $string
     *
     * @return string
     */
    public static function qj2bj($string)
    {
        $convert_table = Array(
            '０' => '0',
            '１' => '1',
            '２' => '2',
            '３' => '3',
            '４' => '4',
            '５' => '5',
            '６' => '6',
            '７' => '7',
            '８' => '8',
            '９' => '9',
            'Ａ' => 'A',
            'Ｂ' => 'B',
            'Ｃ' => 'C',
            'Ｄ' => 'D',
            'Ｅ' => 'E',
            'Ｆ' => 'F',
            'Ｇ' => 'G',
            'Ｈ' => 'H',
            'Ｉ' => 'I',
            'Ｊ' => 'J',
            'Ｋ' => 'K',
            'Ｌ' => 'L',
            'Ｍ' => 'M',
            'Ｎ' => 'N',
            'Ｏ' => 'O',
            'Ｐ' => 'P',
            'Ｑ' => 'Q',
            'Ｒ' => 'R',
            'Ｓ' => 'S',
            'Ｔ' => 'T',
            'Ｕ' => 'U',
            'Ｖ' => 'V',
            'Ｗ' => 'W',
            'Ｘ' => 'X',
            'Ｙ' => 'Y',
            'Ｚ' => 'Z',
            'ａ' => 'a',
            'ｂ' => 'b',
            'ｃ' => 'c',
            'ｄ' => 'd',
            'ｅ' => 'e',
            'ｆ' => 'f',
            'ｇ' => 'g',
            'ｈ' => 'h',
            'ｉ' => 'i',
            'ｊ' => 'j',
            'ｋ' => 'k',
            'ｌ' => 'l',
            'ｍ' => 'm',
            'ｎ' => 'n',
            'ｏ' => 'o',
            'ｐ' => 'p',
            'ｑ' => 'q',
            'ｒ' => 'r',
            'ｓ' => 's',
            'ｔ' => 't',
            'ｕ' => 'u',
            'ｖ' => 'v',
            'ｗ' => 'w',
            'ｘ' => 'x',
            'ｙ' => 'y',
            'ｚ' => 'z',
            '　' => ' ',
            '：' => ':',
            '。' => '.',
            '？' => '?',
            '，' => ',',
            '／' => '/',
            '；' => ';',
            '［' => '[',
            '］' => ']',
            '｜' => '|',
            '＃' => '#',
        );
        return strtr($string, $convert_table);
    }

    /**
     * 字符串截取
     * @param string $str
     * @param int $strlen
     * @param int $other
     * @return string
     */
    public static function doStrOut($str, $strlen = 10, $other = 0)
    {
        if (empty($str)) {
            return $str;
        }
        $str = @iconv('UTF-8', 'GBK', $str);
        $j = 0;
        for ($i = 0; $i < $strlen; $i++) {
            if (ord(substr($str, $i, 1)) > 0xa0) {
                $j++;
            }
        }
        if ($j % 2 != 0) {
            $strlen++;
        }
        $rstr = @substr($str, 0, $strlen);
        $rstr = @iconv('GBK', 'UTF-8', $rstr);
        if (strlen($str) > $strlen && $other) {
            $rstr .= '...';
        }
        return $rstr;
    }

    /**
     * 字符串截取
     * Enter description here ...
     * @param string $Str 为截取字符串
     * @param int $Length 需要截取的长度
     * @param string $dot 后缀
     * @return  string
     */
    public static function doSubstr($str, $len, $dot = '...')
    {
        // 检查长度
        if (mb_strwidth($str, 'UTF-8') <= $len) {
            return $str;
        }
        // 截取
        $i = 0;
        $tlen = 0;
        $tstr = '';
        while ($tlen < $len) {
            $chr = mb_substr($str, $i, 1, 'utf8');
            $chrLen = ord($chr) > 127 ? 2 : 1;

            if ($tlen + $chrLen > $len)
                break;

            $tstr .= $chr;
            $tlen += $chrLen;
            $i++;
        }
        if ($tstr != $str) {
            $tstr .= $dot;
        }

        return $tstr;
    }

    /**
     * 过滤特殊字符
     * @return mixed|string
     */
    public static function replaceHtmlAndJs($string)
    {
        if (get_magic_quotes_gpc()) {
            $string = stripslashes($string);
        }
        $string = mb_ereg_replace('^(　| )+', '', $string);
        $string = mb_ereg_replace('(　| )+$', '', $string);
        $string = mb_ereg_replace('　　', "\n　　", $string);
        //       $string    =   preg_replace('/select|insert|and|or|update|delete|\'|\/\*|\*|\.\.\/|\.\/|union|into|load_file/i','',$string);
        $string = htmlspecialchars($string, ENT_QUOTES);
        return $string;
    }

    /**
     * 检测是否在设定的两个数之间
     * 结果总是出现在边界
     * 例如:
     * domid(985,0,100)=100 无边界设置
     * domid(985,0,100,20,96)=96 大边界
     * domid(0,0,100,20,96)=20 小边界
     *
     * @param int $it 一个整数
     * @param int $min 边界,较小的数
     * @param int $max 边界,较大的数
     * @param int $min_de 小边界的默认数值
     * @param int $max_de 大边界的默认数值
     * @return int
     */
    public static function doMid($it, $min, $max, $min_de = null)
    {
        $it = (int)$it;
        if (null !== $min_de && $it == 0) {
            $it = $min_de;
        } else {
            $it = max($it, $min);
            $it = min($it, $max);
        }
        return $it;
    }

    public static function doSetId($id)
    {
        if (!empty($id)) {
            $id = preg_replace(array('/[^\d,]/', '/[,]{2,}/'), array('', ','), $id);
            $id = trim($id, ',');
            !$id && $id = 0;
        } else {
            $id = 0;
        }
        return $id;
    }

    /**
     * 解析json串
     * @param type $json_str
     * @return type
     */
    public static function isJson($json_str)
    {
        $json_str = str_replace('＼＼', '', $json_str);
        json_decode($json_str, true);
        return (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }

    /**
     * 验证数字是否合法
     * @param int $num 需要验证的数字
     * @param array $options => array( 数字验证区间
     *      'min' =>1,
     *      'max' => 20,
     *  )
     */
    public static function isInt($num, $options = array())
    {
        if (!preg_match('/^-?\d+$/', $num)) {
            return false;
        }
        if (empty($options)) {
            return true;
        }
        if (isset($options['min']) && is_int($options['min'])) {
            if ($options['min'] > $num) {
                return false;
            }
        }
        if (isset($options['max']) && is_int($options['max'])) {
            if ($options['max'] < $num) {
                return false;
            }
        }
        return true;
    }

    /**
     * 校验学部
     *
     * @param $departId 学部id
     * @return bool
     */
    public static function checkDepart($departId)
    {
        if (empty($departId)) return false;

        if (!GendString::isInt($departId) || !isset(Services_Common_Departmentgrade::$department[$departId])) {
            return false;
        }

        return true;
    }

    /**
     * 校验学科
     *
     * @param $sujectId 学科id
     * @return bool
     */
    public static function checkSubject($sujectId)
    {
        if (empty($sujectId)) return false;

        if (!GendString::isInt($sujectId) || !isset(Services_Common_Subjects::$subjects[$sujectId])) {
            return false;
        }

        return true;
    }

    /**
     * 校验年级
     *
     * @param $gradeId 年级id
     * @return bool
     */
    public static function checkGrade($gradeId)
    {
        if (empty($gradeId)) return false;

        if (!GendString::isInt($gradeId) || !isset(Services_Common_Grades::$grades[$gradeId])) {
            return false;
        }

        return true;
    }

    /**
     * 校验省份
     *
     * @param $provinceId 省份id
     * @return bool
     */
    public static function checkProvince($provinceId)
    {
        if (empty($provinceId)) return false;

        if (!GendString::isInt($provinceId) || !isset(Services_Common_Provinces::$provinces[$provinceId])) {
            return false;
        }

        return true;
    }

    /**
     * 格式化学部
     *
     * @return array
     */
    public static function getDepartments()
    {
        return self::formatComData(Services_Common_Departmentgrade::$department);
    }

    /**
     * 格式化学科(返回学部学科的关联数组)
     *
     * @param $subjects array 学部学科关联数组
     *
     * @return array
     */
    public static function getSubjects($subjects = [])
    {
        if (empty($subjects)) {
            $subjects = Services_Common_Departmentgrade::$subject;
        }

        foreach ($subjects as $k => $v) {
            $data = [];
            foreach ($v as $key => $val) {
                $tmp['id'] = (string)$key;
                $tmp['value'] = $val;
                $data[] = $tmp;
            }
            $backdata[$k] = $data;
        }

        return $backdata;
    }

    /**
     * 单独获取全部学科
     *
     * @return array
     */
    public static function getAllSubjects()
    {
        $subjects = Services_Common_Subjects::$subjects;

        $data = [];
        foreach ($subjects as $val) {
            $data[$val['id']] = $val['name'];
        }

        $data = self::formatComData($data);

        return $data;
    }

    /**
     * 格式化年级
     *
     * @return array
     */
    public static function getGrades()
    {
        $grades = Services_Common_Departmentgrade::$grade;

        foreach ($grades as $k => $v) {
            $data = [];
            foreach ($v as $key => $val) {
                $tmp['id'] = (string)$key;
                $tmp['value'] = $val;
                $data[] = $tmp;
            }
            $backdata[$k] = $data;
        }

        return $backdata;
    }

    /**
     * 格式化省份
     *
     * @return array
     */
    public static function getProvinces()
    {
        $provinces = Services_Common_Provinces::$provinces;

        $data = [];
        foreach ($provinces as $val) {
            $data[$val['id']] = $val['name'];
        }

        $data = self::formatComData($data);

        return $data;
    }

    /**
     * 格式公共格式的数据
     *
     * 返回格式如下
     * [
     *  [
     *    'id' => 1,
     *    'value' => 小学
     *  ]
     * ]
     *
     * 传入参数格式
     * [1 => 小学, 2 => 语文]
     *
     * @param array $data
     * @return array
     */
    public static function formatComData($data = [])
    {
        $backdata = [];
        foreach ($data as $k => $v) {
            $tmp['id'] = (string)$k;
            $tmp['value'] = $v;
            $backdata[] = $tmp;
        }

        return $backdata;
    }

    /**
     * 验证文件地址是否合法
     * @param string $fileUrl
     * @return bool
     */
    public static function checkFileUrl($fileUrl = '')
    {
        if (empty($fileUrl)) {
            return false;
        }

        $match = array();
        if (!preg_match('/^(https|http):\/\/[a-z0-9]+\.Guoimg\.com\/.*$/i', $fileUrl, $match)) {
            return false;
        }

        return true;
    }

    /**
     * 获取压缩的图片地址
     * @param string $url 原图地址
     * @param int $scale 压缩比
     * @param int $width 图片地址的宽度
     * @param int $height 图片地址的高度
     * @return string
     */
    public static function getCompressedImgUrl($url='',$scale=1,$width=0,$height=0)
    {
        if (empty($url)) {
            return $url;
        }
        if (empty($width) || empty($height)) {
            $size = @getimagesize($url);
            if ($size === false) {
                return $url;
            }
            $width = $size[0];
            $height = $size[1];
        }
        $pathExtension = '.'.pathinfo($url, PATHINFO_EXTENSION);
        $w = round($width * $scale);
        $h = round($height * $scale);
        $newExtension = "_{$w}x{$h}{$pathExtension}";

        return str_replace($pathExtension, $newExtension, $url);
    }

    /*
     * 校验日期
     *
     * @param  $date string
     * @param  $type int    0:校验 YY-MM-DD 1:校验 YY-MM-DD H:i:s
     *
     * @return bool
     */
    public static function checkDate($date, $type = 0)
    {
        $reg = '/^\d{4}-\d{2}-\d{2}$/s';
        if ($type == 1) {
            $reg = '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/s';
        }

        if (preg_match($reg, $date)) {
            return true;
        }

        return false;
    }

    /**
     * 检测/过滤字符串XSS注入
     *
     * @param string  $val 输入字符串（数字类型请用intval或者floatval转换处理）
     * @param boolean $only_check 是否仅检测（true:仅检测，返回结果为boolean; 默认:返回过滤字符串）
     * @param string  $allowable_html_tags 允许的html标签（'none':清除全部html标签; '<p><a>':保留p和a标签，同strip_tags第二个参数; 默认:不做处理）
     * @param boolean $htmlspecialchars 是否使用htmlspecialchars函数处理返回结果（ture:使用;默认:不使用）
     *
     * @return string/boolean
     */
    public static function removeXSS($val, $only_check = false, $allowable_html_tags = '', $htmlspecialchars = false)
    {
        $is_dangerous = false;

        //如果设置了过滤全部html标签，则过滤全部标签
        if ($allowable_html_tags != 'all') {

            if ($allowable_html_tags == 'none') {

                $val = strip_tags($val);
            } else if ($allowable_html_tags != '') {

                $val = strip_tags($val, $allowable_html_tags);
            }

        }

        $search = 'abcdefghijklmnopqrstuvwxyz';
        $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $search .= '1234567890!@#$%^&*()';
        $search .= '~`";:?+/={}[]-_|\'\\';

        for ($i = 0; $i < strlen($search); $i++) {
            $val = preg_replace('/(&#[xX]0{0,8}' . dechex(ord($search[$i])) . ';?)/i', $search[$i], $val);
            $val = preg_replace('/(&#0{0,8}' . ord($search[$i]) . ';?)/', $search[$i], $val);
        }

        $ra = array(
            'javascript', 'vbscript', 'expression', 'applet', 'meta', '<xml', '&lt;xml', '&#60;xml', 'blink', 'link',
            'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base',
            'onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy',
            'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload',
            'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu',
            'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete',
            'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover',
            'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin',
            'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture',
            'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup',
            'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange',
            'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete',
            'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop',
            'onsubmit', 'onunload'
        );

        $found = true;
        while ($found == true) {

            $val_before = $val;

            for ($i = 0; $i < sizeof($ra); $i++) {

                $pattern = '/';

                for ($j = 0; $j < strlen($ra[$i]); $j++) {
                    if ($j > 0) {
                        $pattern .= '(';
                        $pattern .= '(&#[xX]0{0,8}([9ab]);)';
                        $pattern .= '|';
                        $pattern .= '|(&#0{0,8}([9|10|13]);)';
                        $pattern .= ')*';
                    }
                    $pattern .= $ra[$i][$j];
                }

                $pattern .= '/i';

                $replacement = substr($ra[$i], 0, 2) . '<x>' . substr($ra[$i], 2);

                $val = preg_replace($pattern, $replacement, $val);

                if ($val_before == $val) {
                    $found = false;
                } else {
                    $is_dangerous = true;
                }
            }

        }

        if ($only_check === false) {
            if ($htmlspecialchars === false) {
                return $val;
            } else {
                return htmlspecialchars($val);
            }
        } else {
            return $is_dangerous;
        }
    }
}
