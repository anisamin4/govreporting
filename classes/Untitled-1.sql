-- =============================================  
-- Author:  <Author,,Name>  
-- Create date: <Create Date,,>  
-- Description: <Description,,>  
  
-- =============================================  
create PROCEDURE [dbo].[ProExcelAddShopData]   
  (  
 @Accountid int ,@NumOfSurveys int  ,  @score int ,  @Start DateTime , @End DateTime , @ReDo bit, @nps int  
 )  
 AS  
BEGIN  
  
print 'a'  
   
  
 -- SET NOCOUNT ON added to prevent extra result sets from  
 -- interfering with SELECT statements.  
 SET NOCOUNT ON;  
  
 --declare @Accountid int  
    
  
    --select top 1 @Accountid = a.accountid   from V2Reports.dbo.accounts a join csi.dbo.AAAFacilities f on f.AAAState+f.FacNum = a.AccountName   where f.ClubCode = @clubcode   and  f.ClientFacNum =@aarnum and f.Active =1 and a.AccountActive =1  
  
 print 'below is accId'  
 print @accountid  
  
  
  
   
--declare @NumOfSurveys int  
declare @OSat decimal(18,2)  
  
DECLARE @Counter INT   
declare @c2 int  
declare @tc int  
declare @mnths as int  
declare @cDate date  
declare @Who varchar(50)  
declare @Seed int  
declare @Facnum varchar(50)  
declare @OkToRun int  
declare @Stars decimal(18,2)  
declare @clubcode varchar(50)  
  
  
select @Accountid = a.accountid , @clubcode = f.ClubCode, @Facnum =f.ClientFacNum   from V2Reports.dbo.accounts a join csi.dbo.AAAFacilities f on f.AAAState+f.FacNum = a.AccountName  
 where a.AccountId = @Accountid  
  
  
  
Set @Who = 'Dealer CSI'  
--Set @Start = '3/1/22'  
--Set @End = '3/31/23'  
--Set @NumOfSurveys  = @numsurveys  
Set @OSat = @score  
Set @Stars = 4.78 -- this is the slave use this valueto help figure out the  % score  
set @OkToRun =1-- 1 will allow to run any other value will not import  
--set @ReDo = 0 -- 1 will  delete all factory records from the data range above and re enter  
--set @nps = 0  -- set this to 1 if you want an NPS score to appear  
  
select 'will run for '+ accountfullname , d.*    from V2Reports.dbo.accounts  a join csi.dbo.AAAFacilities f on f.AAAState+f.FacNum = a.AccountName  
left join csi.dbo.dealerCSI d on d.accountid = a.accountid  
where a.accountid = @accountid  
  
if @redo = 1 begin  
  
  Delete from csi.dbo.IVRAns  
  --select i.*   
  from [CustomerData].[dbo].[Customers] c join csi.dbo.csimaster m on m.CustomerTableCustomerID = c.CustomerID   join csi.dbo.ivrans i on i.csimasterid = m.csiid where c.accountid = @accountid    and insertedby = 'Excel Upload' and c.firstname like 'custo
mer %' and LastModifiedDate >= @Start and LastModifiedDate <= @End  
  
  delete from csi.dbo.csimaster   
  --select m.*   
  from [CustomerData].[dbo].[Customers] c join csi.dbo.csimaster m on m.CustomerTableCustomerID = c.CustomerID   where c.accountid = @accountid    and insertedby = 'Excel Upload' and c.firstname like 'customer %' and LastModifiedDate >= @Start and LastMod
ifiedDate <= @End  
  
  delete from customerdata.dbo.customers   
  --select *   
  from [CustomerData].[dbo].[Customers]   where accountid = @accountid    and insertedby = 'Excel Upload' and firstname like 'customer %' and LastModifiedDate >= @Start and LastModifiedDate <= @End  
  
  delete from csi.dbo.dealerCSI   
   -- select *   
   from csi.dbo.dealerCSI where accountid =@accountid  and NumberOfSurveys = @NumOfSurveys and startdate = @Start and enddate =@End  
  
end  
  
-- select * from   csi.dbo.dealerCSI   
if exists ( select top 1 who from csi.dbo.dealerCSI where clubcode = @clubcode and facnum = @facnum and numberofsurveys = @NumOfSurveys  ) begin  
           select top 1 who from csi.dbo.dealerCSI where clubcode = @clubcode and facnum = @facnum  
       
             select  100* (@Stars /5 )  as [Set the % scale to below value] , ((5*@NumOfSurveys)- (@Stars * @NumOfSurveys))/2 as [5s] , @Stars as Stars  
   select 'DONT RUN DUP'  
   select 'DONT RUN DUP'  
   select 'DONT RUN DUP'  
   select 'DONT RUN DUP'  
   select 'DONT RUN DUP'  
   select 'DONT RUN DUP'  
   set @OkToRun =2   
end   
  
  
select  100* (@Stars /5 )  as [Set the % scale to below value] , ((5*@NumOfSurveys)- (@Stars * @NumOfSurveys))/2 as [5s] , @Stars as Stars  
  
  
Select @mnths = DateDiff(Month, @Start, @End + 1)  
select 'num of months',@mnths  
select top 1 @seed = id from csi.dbo.IVRAns order by id desc    -- select top 1 * from csi.dbo.IVRAns order by id desc  
  
   IF OBJECT_ID(N'tempdb..#ThirdParyCSIData_Excel_Upload') is not null begin  
   DROP TABLE #ThirdParyCSIData_Excel_Upload   
   end  
  
   
  
   CREATE TABLE #ThirdParyCSIData_Excel_Upload  
   (  
    ID int,  
    DateReceived datetime,  
    ProviderName  varchar(150),  
    AccountID int,  
    FacilityClub varchar(50),  
    FacilityNumber varchar(50),  
    FirstName varchar(50),  
    DateOfService date,  
    DateOfSurvey date,  
    Q1Value decimal(18,2),  
    Q1MaxValue decimal(18,2),  
    Q8Value decimal(18,2),  
    Q8MaxValue decimal(18,2)  
      
   )    
  
  
SET @Counter=1  
set @tc =1  
WHILE ( @Counter <= @mnths)  
BEGIN  
    PRINT 'The counter value is = ' + CONVERT(VARCHAR,@Counter)  
      
  
 SET @c2=1  
  WHILE ( @c2 <= 1+ @NumOfSurveys/@mnths )  
  BEGIN  
     
   if @NumOfSurveys > (select count(*) from #ThirdParyCSIData_Excel_Upload) begin  
    insert into #ThirdParyCSIData_Excel_Upload (DateOfSurvey, FirstName,Q1Value,Q1MaxValue,ID,Q8Value,Q8MaxValue)  
    select  /*@Start,@Counter,*/ dateadd (MM,@Counter-1,@Start), 'Customer ' + CONVERT(VARCHAR,@tc) ,5,5,@tc+ @Seed,  case when @nps = 1 then 10 else null end,   case when @nps = 1 then 10 else null end  
   end  
     
   PRINT '      INsert row here = ' + CONVERT(VARCHAR,@C2)  
   SET @C2  = @c2  + 1  
   SET @TC  = @TC  + 1  
  END  
  
  SET @Counter  = @Counter  + 1  
    
  
END  
  
--select * from #ThirdParyCSIData_Excel_Upload  
  
  
  
  IF OBJECT_ID(N'tempdb..#TempMnt') is not null begin  
   DROP TABLE #TempMnt   
  end  
  
select distinct DateOfSurvey into #TempMnt  from #ThirdParyCSIData_Excel_Upload  
  
--select * from #tempmnt  
  
while ( select 100* (cast( sum( case when q1value > 3 then 1 else 0 end )-1 as decimal (18,2))  / count(*))  from #ThirdParyCSIData_Excel_Upload) > @OSat  begin  
  
  
    DECLARE vastappt CURSOR FOR   
      Select   
       [DateOfSurvey]  
      from #tempmnt  
      
    OPEN vastappt;  
    FETCH NEXT FROM vastappt INTO   
            
          @cDate  
           
  
    WHILE @@FETCH_STATUS = 0  
     BEGIN  
      
     --print 'date in while ' + cast( @cdate as varchar)  
     /*  
     select 'here', @OSat ,  
       cast(100*sum( case when q1value > 3 then 1 else 0 end ) as decimal (18,2)) / cast(count(*) as decimal (18,2))   
     ,cast(100*sum( case when q1value > 3 then 1 else 0 end ) as decimal (18,2)) , cast(count(*) as decimal (18,2))   
       
     , 100 * cast(sum( case when q1value > 3 then 1 else 0 end )-1 as decimal (18,2)) / cast(count(*) as decimal (18,2))   
     ,cast(100*sum( case when q1value > 3 then 1 else 0 end ) as decimal (18,2)) , cast(count(*) as decimal (18,2))   
     from #ThirdParyCSIData_Excel_Upload  
     */  
  
  
     if @nps = 1 begin  
       if ( select 100* (cast( sum( case when q1value > 3 then 1 else 0 end )-1 as decimal (18,2))  / count(*))  from #ThirdParyCSIData_Excel_Upload) > @OSat  begin  
        update #ThirdParyCSIData_Excel_Upload set Q1Value =3, Q8Value =3 where id =   
        (select top 1 id from #ThirdParyCSIData_Excel_Upload where DateOfSurvey = @cDate order by Q1Value desc)  
       end  
     end  
     else begin  
       if ( select 100* (cast( sum( case when q1value > 3 then 1 else 0 end )-1 as decimal (18,2))  / count(*))  from #ThirdParyCSIData_Excel_Upload) > @OSat  begin  
        update #ThirdParyCSIData_Excel_Upload set Q1Value =3 where id =   
        (select top 1 id from #ThirdParyCSIData_Excel_Upload where DateOfSurvey = @cDate order by Q1Value desc)  
       end  
     end  
  
  
  
        
     FETCH NEXT FROM vastappt INTO   
          @cDate  
           
     END;  
    CLOSE vastappt;  
    DEALLOCATE vastappt;  
  
  
end  
  
  
--select * from #ThirdParyCSIData_Excel_Upload  
  
select   
sum(case when q1value in (5,4) then 1  
else 0 end) as top2  
,count(*)  
,100*sum(case when q1value in (5,4) then 1  
else 0 end)   
/ cast(count(*) as decimal (18,2)) as top2Perc  
,@OSat as FromOther  
,avg(q1value) as [5ptScale]  
 from #ThirdParyCSIData_Excel_Upload  
  
 select   
sum(case when q1value in (5,4) then 1  
else 0 end) as top2  
,count(*)  
,100*sum(case when q1value in (5,4) then 1  
else 0 end)   
/ cast(count(*) as decimal (18,2)) as top2PercOnelower  
,@OSat as FromOther  
,avg(q1value) as [5ptScale]  
 from #ThirdParyCSIData_Excel_Upload  
  
  
  select   
sum(case when q1value in (5,4) then 1  
else 0 end) as top2  
,count(*)  
,100*sum(case when q1value in (5,4) then 1  
else 0 end) -0  
/ cast(count(*) as decimal (18,2)) as top2PercOnelower  
,@OSat as FromOther  
,avg(q1value) as [5ptScale]  
 from #ThirdParyCSIData_Excel_Upload  
  
  
   select   
sum(case when q8value in (9,10) then 1  
else 0 end) as top2  
,count(*)  
,100*sum(case when q8value in (9,10) then 1  
else 0 end)    
/ cast(count(*) as decimal (18,2)) as NPSCALc  
,@OSat as NPSFromOther  
,avg(q8value) as [NPS]  
 from #ThirdParyCSIData_Excel_Upload  
  
  
  
 if 1 = @OkToRun begin  
  
DROP TABLE IF EXISTS #TempThirdParyCSIData_Excel_Upload   
  
CREATE TABLE #TempThirdParyCSIData_Excel_Upload  
(  
 ID int,  
 DateReceived datetime,  
 ProviderName  varchar(150),  
 AccountID int,  
 FacilityClub varchar(50),  
 FacilityNumber varchar(50),  
 FirstName varchar(50),  
 LastName varchar(50),  
 phone varchar(50),  
 email varchar(150),  
 [ip] varchar(50),  
 DateOfService varchar(50),  
 DateOfSurvey varchar(50),  
 Q1Value decimal(18,2),  
 Q1MaxValue decimal(18,2),  
 Q2Value decimal(18,2),  
 Q3Value decimal(18,2),  
 Q4Value decimal(18,2),  
 Q5Value decimal(18,2),  
 Q6Value decimal(18,2),  
 Q7Value decimal(18,2),  
 Q8Value decimal(18,2),  
 Q8MaxValue decimal(18,2),  
 OpenEndedComments varchar(MAX),  
 MemberAnswer varchar(MAX)  
)    
-- fix accountid based on club code and facility number   
  
  
insert into #TempThirdParyCSIData_Excel_Upload  
(  
 ID ,  
 DateReceived ,  
 ProviderName ,  
 AccountID ,  
 FacilityClub ,  
 FacilityNumber ,  
 FirstName ,  
 LastName ,  
 phone ,  
 email ,  
 [ip],  
 DateOfService ,  
 DateOfSurvey ,  
 Q1Value ,  
 Q1MaxValue ,  
 Q2Value ,  
 Q3Value ,  
 Q4Value ,  
 Q5Value ,  
 Q6Value ,  
 Q7Value ,  
 Q8Value ,  
 Q8MaxValue ,  
 OpenEndedComments,  
 MemberAnswer   
    
)  
select   
    ID,  
 DateOfSurvey,  
 @Who,  
 @Accountid,  
 @clubcode,  
 @Facnum,  
 FirstName,  
 '' as LastName,  
 '' as phone,  
 '' as email,  
 '192.168.78.1' as [ip],  
 DateOfSurvey,  
 DateOfSurvey,  
 Q1Value,  
 Q1MaxValue ,  
 null as Q2Value,  
 null as Q3Value,  
 null as Q4Value,  
 null as Q5Value,  
 null as Q6Value,  
 null as Q7Value,  
  
 case when @nps =1 then Q8Value else null end,  
 case when @nps =1 then Q8MaxValue else null end,  
  
 '' as OpenEndedComments,  
 '' as MemberAnswer  -- select *  
    
 from #ThirdParyCSIData_Excel_Upload  
    
  
  select * from #TempThirdParyCSIData_Excel_Upload  
    
  
    
 declare @id as int  
 declare @NewCustomerId as int  
 declare @NewCSIID as int  
  
 DECLARE @getID CURSOR  
 SET @getID = CURSOR FOR  
  
 select distinct t.ID -- ,t.*  , c.ClientCustomerID  
 from #TempThirdParyCSIData_Excel_Upload T left join customerdata.dbo.customers c on t.ID = c.ClientCustomerID and c.InsertedBy = 'Excel Upload'  
 where   
 -- t.FacilityClub = '5' and t.FacilityNumber ='52818' and  
 c.customerid is null   
 and (t.firstname not like '%test%' or t.lastname not like '%test%' or  t.openendedcomments not like '%test%' )  
 and t.ID > 6  
 and cast(q1value as int) > -1  
 and isdate(dateofservice) = 1  
 and  isdate(dateofsurvey) = 1  
 and  isdate(datereceived) = 1  
  
  
  
  
  
 OPEN @getID  
 FETCH NEXT  
 FROM @getID INTO @ID  
 WHILE @@FETCH_STATUS = 0  
 BEGIN  
  
  
  
 print @id  
  
 print 'about to insert into customers'  
   
  
  INSERT INTO [CustomerData].[dbo].[Customers]               
     ([ClientCustomerID]    
     ,Company            
     ,[CustNameCombo]              
     ,[FirstName]              
     ,[LastName]              
     ,[EmailAddress]              
     --,[EmailAddress2]              
     ,[CellPhone]                
     ,[AccountID]              
     ,[InsertDate]              
     ,[InsertedBy]  
     ,LastModifiedDate  
     ,LocationID)   
  
     select t.id, t.providername, t.firstname+' '+t.Lastname, t.firstname, t.lastname,t.email,t.phone, a.accountid, t.datereceived, 'Excel Upload' ,t.[DateOfService],t.id  
     from   #TempThirdParyCSIData_Excel_Upload T  
   join  csi.dbo.AAAFacilities F with (nolock) on f.ClientFacNum = t.facilitynumber and f.ClubCode = t.facilityclub join v2reports.dbo.accounts a with (nolock) on a.accountname = f.AAAState +f.FacNum  
   where t.ID = @ID  
  
   set @NewCustomerId = SCOPE_IDENTITY()  
  
  
  
    --   declare @Newcustomerid int declare @NewCSIID as int set @NewCustomerID = 16852442  
    INSERT INTO CSI.dbo.CSIMaster(FirstName,LastName,acnm,AccountID,  
    WorkPhone,HomePhone,CellPhone,Email,DateInsert,CustomerID,notes,  
   ServiceDateTime,LocationID,CSIURL,status,CustState,Importnotes,CustomerTableCustomerID)   
  
  
  
  
  
  
   SELECT DISTINCT Customers.FirstName, Customers.LAStName, (select accountname from V2Reports.dbo.accounts where accountid = Customers.accountid) as acnm ,  Customers.accountid,    
     Customers.WorkPhone, Customers.HomePhone, Customers.CellPhone, Customers.EmailAddress, Customers.InsertDate,clientcustomerid, Customers.Notes,  
     customers.LastModifiedDate,'1','none','1' as status,'na','Excel Upload',Customers.CustomerID   
      
   FROM customerdata.dbo.Customers   
   WHERE (Customers.customerid  = @NewCustomerId)   
      
   set @NewCSIID = SCOPE_IDENTITY()   
     
   --select @NewCSIID    
   --select top 5 * from CSIMaster order by csiid desc  
  
   --delet  from CSIMaster where csiid =5954399  
   --if (select t.SurveyMethod from [192.168.75.2].[AutoRepairSMSTransferInfo].[dbo].[ThirdParyCSIData_Excel_Upload] t where id = @ID)= 'web' begin  
   if 1=1 begin  
  
    -- select top 60 *  from ivrans where ans19 = '1' order by id desc      select * from [192.168.75.2].[AutoRepairSMSTransferInfo].[dbo].[ThirdParyCSIData_Excel_Upload]  
    --  
    Insert into IVRAns(  
    WebString,Acnm,QsAcnm,QuestionSet,IWRMasterID,  
    DateTimeOfCall,EndCallTime,NumberCalledFrom  
    ,ans19 --q1  
    ,ans20 --q1  
    ,ans21 --q2  
    ,ans22 --q3  
    ,ans23 --q4  
    ,ans24 --q5  
    ,ans25 --q6  
    ,ans26 --q7  
    ,ans27 --q8  
    ,ans28  
    ,note2,  
    Complete,CSIMasterID,FirstName,LastName,Status,AccountID,CheckedForAlert,IpAddress  
    )  
  
    select   
    'www.ESatsurv.com/?aaaacsc',c.Acnm,w.QuestionSetAccount, 7 --  w.QuestionSet  
    ,w.ID  
    ,c.DateInsert,c.DateInsert,cus.company  
    ,'1'as ans19  
    ,cast(cast (t.q1value as int) +(-1* (t.Q1MaxValue - 4)) as int) as q1  --1 20  
    ,cast(cast (t.q2value as int)+(-1* (t.Q1MaxValue - 4))  as int) as q2 --2 21  
    ,cast(cast (t.q3value as int)+(-1* (t.Q1MaxValue - 4))  as int)  as q3 --3 22  
    ,cast(cast (t.q4value as int)+(-1* (t.Q1MaxValue - 4))   as int) as q4 --4 23  
    ,cast(cast (t.q5value as int)+(-1* (t.Q1MaxValue - 4))  as int)  as q5 --5 24  
    ,cast(cast (t.q6value as int)+(-1* (t.Q1MaxValue - 4))  as int)  as q6 --6 25  
    ,cast(cast (t.Q7Value as int)+(-1* (t.Q1MaxValue - 4))  as int)  as q6 --6 26  
    ,cast (t.Q8Value as int)  --(-1* (t.Q8MaxValue - 4))   as int) as q8--7   
      
    ,t.memberanswer  ,substring(t.openendedcomments,1,4000)  
    ,'yes',c.CSIID,c.FirstName,c.Lastname,'complete',c.AccountID,'1',t.ip  
    -- select top 1 *  
    from CSIMaster c with (nolock)  join V2Reports.dbo.IWRMaster w with (nolock) on c.Acnm = w.AccountName   
    join CustomerData.dbo.customers cus with (nolock) on cus.CustomerID = c.CustomerTableCustomerID   
    join #TempThirdParyCSIData_Excel_Upload t with (nolock) on t.id = cus.LocationID  
    where c.csiid = @NewCSIID  
   end  
  
  
  
FETCH NEXT  
FROM @getID INTO @ID  
END  
CLOSE @getID  
DEALLOCATE @getID  
  
--DROP TABLE #TempThirdParyCSIData_Excel_Upload  
   
 -- select * from #TempThirdParyCSIData_Excel_Upload  
   
INSERT INTO [dbo].[DealerCSI]  
      ([who]  
           ,[startdate]  
           ,[enddate]  
           ,[NumberOfSurveys]  
           ,[osat]  
           ,[accountid]  
           ,[ClubCode]  
           ,[Facnum]  
     ,insertdate  
     ,stars)  
     
   select   
           @who,    
           @start,    
           @end,   
           @NumOfSurveys,   
           @osat,    
           @accountid,   
          @ClubCode,   
           @Facnum  ,  
     getdate(),  
     @stars  
   
  
  
  
  
 end -- if 1 = @OkToRun begin  
  
  
  
  
  
  
  
  
END  
   