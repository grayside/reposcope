<?php
/**
 * Minimally functional generic implementation.
 *
 * @note
 *   This is barely more advanced than an interface. Recommendation:
 *   don't actually use as "RepoScope", instead use just for the cmd()
 *   function.
 */
class RepoScope {
  var $tool_path;
  var $context;
  var $options = array();

  function __construct($context = NULL, $tool_path = NULL) {
    $this->context = $context;
    $this->tool_path = $tool_path;
  }

  /**
   * Execute the specified git command.
   */
  public function cmd($command, $reset = FALSE) {
    static $cmds;

    if (!isset($cmds) || $reset) {
      $cmds = array();
    }

    if (!isset($cmds[$command])) {
      $command = $this->tool_path . ' ' . $this->buildOptions($reset) . ' ' . $command;
      $cmds[$command] = exec($command);
    }

    return $cmds[$command];
  }

  /**
   * Compile git command parameters into commandline options.
   */
  protected function buildOptions($reset = FALSE) {
    static $opt;

    if (!isset($opt) || $reset) {
      $opt = '';
      foreach ($this->options as $name => $value) {
        if (empty($value)) {
          $opt .= strlen($name) == 1 ? '-' . $name : '--' . $name;
        }
        else {
          $opt .= '--' . $name . '="' . $value . '"';
        }
      }
    }
  }

  /**
   * Set option for command-line parameters.
   */
  public function setOpt($name, $value) {
    $this->options[$name] = $value;
  }

  /**
   * Get specified global-use command-line parameter.
   */
  public function getOpt($name) {
    return isset($this->options[$name]) ? $this->options[$name] : NULL;
  }
  
  /**
   * Set a tool to use for command running.
   *
   * This is an alternative to a messy constructor.
   */
  public function setTool($path) {
    $this->tool_path = $path;
  }

  /**
   * Check if the code path is a code repository.
   */
  public function isRepository() {
    return TRUE;
  }

  /**
   * Grabs an assortment of interesting information about the latest commit.
   *
   * @param $item
   *   The key of the specific data of interest.
   * @param $reset
   *   Default to FALSE. If TRUE, rebuilds the commit data.
   */
  protected function getCommitData($item = NULL, $reset = FALSE) {
    return array();
  }
}

/**
 * Defines a Git implementation of RepoScope.
 */
class GitScope extends RepoScope {
  /**
   * Constructor for GitScope.
   *
   * @param $context
   *   Array of git repo context parameters.
   *   - path: Directory location of the Git repository to target with scope.
   * @param $tool_path
   *   Allows specifying path to a specific command-line tool on the system,
   *   such as alternate git installations. Defaults to the 'git' in PATH.
   *
   * @return
   *   New GitScope object.
   */
  function __construct($context = NULL, $tool_path = 'git') {
    parent::__construct($context, $tool_path);
    $repo = is_array($context) && isset($context['path']) ? $context['path'] : $context;
    if (!empty($repo)) {
      if (!is_dir($repo)) {
        return FALSE;
      }
      $this->setOpt('work-tree', $repo);
      $this->setOpt('git-dir', $repo . '/.git');      
    }
  }

  /**
   * Check if the active repository is a git repository.
   */
  public function isRepository() {
    // If explicitly setting the repo, assume a .git directory means valid repo.
    if (is_dir($this->getOpt('git-dir'))) {
      return TRUE;
    }
    return (bool) $this->cmd('rev-parse --git-dir 2> /dev/null');
  }

  /**
   * Get most recent tag.
   */
  public function getLastTag($reset = FALSE) {
    return $this->gitDescribe('last_tag', $reset);
  }

  /**
   * Get the number of commits since the most recent tag.
   */
  public function getCommitsSinceLastTag($reset = FALSE) {
    return $this->gitDescribe('commits_since', $reset);
  }

  /**
   * Get and parse the results of the 'git describe' command.
   *
   * This centralized function can be directly used, but it is here primarily
   * so the individual pieces of data collected from 'git describe' only run
   * off a single shell call.
   */
  public function gitDescribe($item = NULL, $reset = FALSE) {
    static $info;

    if (empty($info) || $reset) {
      $output = $this->cmd('describe --tags --long', $reset);
      if (empty($output)) {
        $info = $output;
        return NULL;
      }
      preg_match('/(.+)\-([\d]+)\-([\d\w]+)/', $output, $matches);
      $info = array(
        'all' => $output,
        'last_tag' => $matches[1],
        'commits_since' => $matches[2],
      );
    }

    return isset($item) && isset($info[$item]) ? $info[$item] : $info['all'];
  }

  /**
   * Grabs an assortment of interesting information about the latest commit.
   *
   * @param $item
   *   The key of the specific data of interest.
   * @param $reset
   *   Default to FALSE. If TRUE, rebuilds the commit data.
   *
   * @return
   *   Array of commit info. Currently provided pieces include:
   *     - commit_id: Short id of the commit.
   *     - commit_id_long: Full id of the commit.
   *     - date: Date of the commit.
   *     - message: Message describing the commit.
   *     - author: Name of the author.
   *     - author_email: Email address of the author.
   *     - committer: Name of the committer.
   *     - committer_email: Email address of the committer.
   */
  public function getCommitInfo($item = NULL, $reset = FALSE) {
    static $info;

    if (empty($info) || $reset) {
      $output = $this->cmd('log -1 --pretty=format:"%an---%ae---%ad---%s---%H---%h---%cn---%ce"', $reset);
      list ($info['author'], $info['author_email'], $info['date'], $info['message'], $info['commit_id_long'], $info['commit_id'], $info['committer'], $info['committer_email']) = explode('---', $output);
    }

    if (isset($item)) {
      return isset($info[$item]) ? $info[$item] : NULL;
    }
    return $info;
  }

  /**
   * Get the current branch name.
   */
  public function getCurrentBranch($reset = FALSE) {
    return $this->getTreeishName('HEAD', $reset);
  }

  /**
   * Get the branch name associated with the specified treeish.
   */
  public function getTreeishName($treeish, $reset = FALSE) {
    return $this->cmd('name-rev --name-only ' . $treeish);
  }
}
