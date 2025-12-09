program LegacyCSV;

{$mode objfpc}{$H+}

uses
  SysUtils, Classes, DateUtils, Process, StrUtils;

function GetEnvDef(const name, def: string): string;
var v: string;
begin
  v := GetEnvironmentVariable(name);
  if v = '' then Exit(def) else Exit(v);
end;

function RandFloat(minV, maxV: Double): Double;
begin
  Result := minV + Random * (maxV - minV);
end;

function RandBool: Boolean;
begin
  Result := Random(2) = 1;
end;

function EscapeCSV(const s: string): string;
var
  t: string;
begin
  t := StringReplace(s, '"', '""', [rfReplaceAll]);
  if (Pos(',', t) > 0) or (Pos('"', t) > 0) or (Pos(#10, t) > 0) or (Pos(#13, t) > 0) then
    Result := '"' + t + '"'
  else
    Result := t;
end;

procedure WriteCSV(const fullpath: string; rows: Integer);
var
  f: TextFile;
  i: Integer;
  recorded_at: string;
  voltage, temp: Double;
  flagA, flagB: Boolean;
  note: string;
  countVal: Integer;
  fn: string;
begin
  fn := ExtractFileName(fullpath);
  AssignFile(f, fullpath);
  Rewrite(f);
  Writeln(f, 'recorded_at,flag_A,flag_B,voltage,temp,count,note,source_file');
  for i := 1 to rows do
  begin
    recorded_at := FormatDateTime('yyyy-mm-dd"T"hh:nn:ss"Z"', IncSecond(Now, - (rows - i) * 60));
    voltage := RandFloat(3.2, 12.6);
    temp := RandFloat(-50.0, 80.0);
    countVal := Random(1000);
    flagA := RandBool;
    flagB := RandBool;
    note := Format('Sample note %d at %s', [i, recorded_at]);

    Writeln(f,
      recorded_at + ',' +
      IfThen(flagA, 'ИСТИНА', 'ЛОЖЬ') + ',' +
      IfThen(flagB, 'ИСТИНА', 'ЛОЖЬ') + ',' +
      FormatFloat('0.00', voltage) + ',' +
      FormatFloat('0.00', temp) + ',' +
      IntToStr(countVal) + ',' +
      EscapeCSV(note) + ',' +
      fn
    );
  end;
  CloseFile(f);
end;

procedure CSVToHTML(const csvPath, htmlPath: string);
var
  sl: TStringList;
  outF: TextFile;
  i, j: Integer;
  cols: TStringList;
  line: string;
begin
  if not FileExists(csvPath) then Exit;
  sl := TStringList.Create;
  cols := TStringList.Create;
  try
    sl.LoadFromFile(csvPath);
    AssignFile(outF, htmlPath);
    Rewrite(outF);
    Writeln(outF, '<!doctype html><html><head><meta charset="utf-8"><title>' + ExtractFileName(csvPath) + '</title>');
    Writeln(outF, '<style>table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:6px;text-align:left}th{background:#f4f4f4}</style>');
    Writeln(outF, '</head><body>');
    Writeln(outF, '<h2>Preview: ' + ExtractFileName(csvPath) + '</h2>');
    Writeln(outF, '<table>');
    if sl.Count > 0 then
    begin
      line := sl[0];
      cols.Delimiter := ',';
      cols.StrictDelimiter := True;
      cols.DelimitedText := line;
      Writeln(outF, '<thead><tr>');
      for j := 0 to cols.Count - 1 do
        Writeln(outF, '<th>' + cols[j] + '</th>');
      Writeln(outF, '</tr></thead>');
    end;
    Writeln(outF, '<tbody>');
    for i := 1 to sl.Count - 1 do
    begin
      line := sl[i];
      cols.DelimitedText := line;
      Writeln(outF, '<tr>');
      for j := 0 to cols.Count - 1 do
        Writeln(outF, '<td>' + cols[j] + '</td>');
      Writeln(outF, '</tr>');
    end;
    Writeln(outF, '</tbody></table></body></html>');
    CloseFile(outF);
  finally
    cols.Free;
    sl.Free;
  end;
end;

procedure CreateExcelFallbackXML(const csvPath, xmlPath: string);
var
  sl: TStringList;
  outF: TextFile;
  i, j: Integer;
  cols: TStringList;
  line: string;
begin
  sl := TStringList.Create;
  cols := TStringList.Create;
  try
    sl.LoadFromFile(csvPath);
    AssignFile(outF, xmlPath);
    Rewrite(outF);
    Writeln(outF, '<?xml version="1.0"?>');
    Writeln(outF, '<?mso-application progid="Excel.Sheet"?>');
    Writeln(outF, '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"');
    Writeln(outF, ' xmlns:o="urn:schemas-microsoft-com:office:office"');
    Writeln(outF, ' xmlns:x="urn:schemas-microsoft-com:office:excel"');
    Writeln(outF, ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">');
    Writeln(outF, '<Worksheet ss:Name="Data">');
    Writeln(outF, '<Table>');
    for i := 0 to sl.Count - 1 do
    begin
      line := sl[i];
      cols.Delimiter := ',';
      cols.StrictDelimiter := True;
      cols.DelimitedText := line;
      Writeln(outF, '<Row>');
      for j := 0 to cols.Count - 1 do
      begin
        Writeln(outF, '<Cell><Data ss:Type="String">' + StringReplace(cols[j], '&', '&amp;', [rfReplaceAll]) + '</Data></Cell>');
      end;
      Writeln(outF, '</Row>');
    end;
    Writeln(outF, '</Table>');
    Writeln(outF, '</Worksheet>');
    Writeln(outF, '</Workbook>');
    CloseFile(outF);
  finally
    cols.Free;
    sl.Free;
  end;
end;

procedure CreateExcelFromCSV(const csvPath: string);
var
  xmlPath: string;
begin
  // генерируем XML (SpreadsheetML 2003) и сохраняем с расширением .xlsx для удобства импорта
  xmlPath := ChangeFileExt(csvPath, '.xlsx');
  CreateExcelFallbackXML(csvPath, xmlPath);
end;

{ Run shell command using TProcess, capture stdout/stderr into TStringList and return exit code }
function RunShellCommand(const cmd: string; out outputLines: TStringList): Integer;
var
  Proc: TProcess;
  Buffer: array[0..2047] of byte;
  BytesRead: LongInt;
  MS: TMemoryStream;
  s: string;
begin
  outputLines := TStringList.Create;
  Proc := TProcess.Create(nil);
  MS := TMemoryStream.Create;
  try
    Proc.Executable := '/bin/sh';
    Proc.Parameters.Clear;
    Proc.Parameters.Add('-c');
    Proc.Parameters.Add(cmd);
    Proc.Options := Proc.Options + [poUsePipes, poWaitOnExit];
    Proc.ShowWindow := swoHIDE;
    try
      Proc.Execute;
    except
      on E: Exception do
      begin
        outputLines.Add('Execute error: ' + E.Message);
        Result := -1;
        Exit;
      end;
    end;

    // read stdout
    MS.Clear;
    while Proc.Output.NumBytesAvailable > 0 do
    begin
      BytesRead := Proc.Output.Read(Buffer, SizeOf(Buffer));
      if BytesRead > 0 then MS.Write(Buffer, BytesRead)
      else Break;
    end;

    if MS.Size > 0 then
    begin
      SetLength(s, MS.Size);
      MS.Position := 0;
      MS.ReadBuffer(s[1], MS.Size);
      outputLines.Text := s;
    end;

    // if no stdout, read stderr
    if (outputLines.Count = 0) and (Proc.Stderr.NumBytesAvailable > 0) then
    begin
      MS.Clear;
      while Proc.Stderr.NumBytesAvailable > 0 do
      begin
        BytesRead := Proc.Stderr.Read(Buffer, SizeOf(Buffer));
        if BytesRead > 0 then MS.Write(Buffer, BytesRead)
        else Break;
      end;
      if MS.Size > 0 then
      begin
        SetLength(s, MS.Size);
        MS.Position := 0;
        MS.ReadBuffer(s[1], MS.Size);
        outputLines.Text := s;
      end;
    end;

    Result := Proc.ExitStatus;
  finally
    Proc.Free;
    MS.Free;
  end;
end;

procedure GenerateAndCopy();
var
  outDir, fn, fullpath, pghost, pgport, pguser, pgpass, pgdb, copyCmd: string;
  rows: Integer;
  outLines: TStringList;
  exitCode: Integer;
begin
  outDir := GetEnvDef('CSV_OUT_DIR', '/data/csv');
  ForceDirectories(outDir);
  fn := 'telemetry_' + FormatDateTime('yyyymmdd_hhnnss', Now) + '.csv';
  fullpath := IncludeTrailingPathDelimiter(outDir) + fn;
  rows := StrToIntDef(GetEnvDef('ROWS_PER_FILE', '10'), 10);

  WriteCSV(fullpath, rows);
  CSVToHTML(fullpath, ChangeFileExt(fullpath, '.html'));
  CreateExcelFromCSV(fullpath);

  pghost := GetEnvDef('PGHOST', 'db');
  pgport := GetEnvDef('PGPORT', '5432');
  pguser := GetEnvDef('PGUSER', 'monouser');
  pgpass := GetEnvDef('PGPASSWORD', 'monopass');
  pgdb   := GetEnvDef('PGDATABASE', 'monolith');

  copyCmd := 'PGPASSWORD=' + pgpass + ' psql "host=' + pghost + ' port=' + pgport + ' user=' + pguser + ' dbname=' + pgdb + '" ' +
             '-c "\copy telemetry_legacy(recorded_at, flag_a, flag_b, voltage, temp, count, note, source_file) FROM ''' + fullpath + ''' WITH (FORMAT csv, HEADER true)"';

  exitCode := RunShellCommand(copyCmd, outLines);
  if exitCode <> 0 then
  begin
    Writeln('COPY command exit code: ', exitCode);
    Writeln(outLines.Text);
  end
  else
    Writeln('[pascal] generated CSV + HTML preview + XLSX(xml) + imported to Postgres');
  outLines.Free;
end;

var
  period: Integer;
begin
  Randomize;
  period := StrToIntDef(GetEnvDef('GEN_PERIOD_SEC', '300'), 300);

  { generate one file immediately at start }
  try
    GenerateAndCopy();
  except
    on E: Exception do WriteLn('Startup generation error: ', E.Message);
  end;

  while True do
  begin
    Sleep(period * 1000);
    try
      GenerateAndCopy();
    except
      on E: Exception do WriteLn('Legacy error: ', E.Message);
    end;
  end;
end.
